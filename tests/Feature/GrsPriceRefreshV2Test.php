<?php

namespace Tests\Feature;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Jobs\Hotel\SyncCitiesJob;
use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob;
use App\Jobs\Hotel\V2\SyncGrsDuePricesJob;
use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GrsPriceRefreshV2Test extends TestCase
{
    public function test_only_approved_config_keys_are_used_with_admin_overrides_and_defaults(): void
    {
        $provider = new Provider(['config' => []]);
        $this->assertSame(GrsRefreshSettings::defaults(), GrsRefreshSettings::from($provider));
        $provider->config = ['price_refresh' => [
            'default_days' => 120,
            'api_cooldown_minutes' => 45,
            'scheduler_enabled' => true,
            // Older deployments may still have these in JSON before reseeding.
            'dispatch_limit' => 1,
            'claim_minutes' => 30,
            'failure_backoff_minutes' => 60,
        ]];
        $this->assertSame([
            'default_days' => 120,
            'api_cooldown_minutes' => 45,
            'scheduler_enabled' => true,
        ], GrsRefreshSettings::from($provider));
        $provider->config = ['price_refresh' => ['default_days' => 0, 'scheduler_enabled' => false]];
        $this->assertSame(90, GrsRefreshSettings::from($provider)['default_days']);
        $this->assertFalse(GrsRefreshSettings::from($provider)['scheduler_enabled']);
    }

    public function test_command_is_independent_and_uses_provider_default_when_days_not_specified(): void
    {
        Bus::fake();
        Http::fake();
        config(['queue.default' => 'database', 'cache.default' => 'database']);

        $this->artisan('grs:sync-prices')->assertExitCode(0);
        Bus::assertDispatched(SyncGrsDuePricesJob::class, fn ($job) =>
            $job->days === null && $job->queue === 'grs-prices');
        Bus::assertNotDispatched(SyncCitiesJob::class);
        Bus::assertNotDispatched(SyncGrsHotelCatalogJob::class);
        Bus::assertNotDispatched(SyncGrsAvailabilityJob::class);
        Http::assertNothingSent();
    }

    public function test_command_accepts_thirty_days_and_rejects_invalid_days(): void
    {
        Bus::fake();
        config(['queue.default' => 'database', 'cache.default' => 'database']);
        $this->artisan('grs:sync-prices --days=30')->assertExitCode(0);
        Bus::assertDispatched(SyncGrsDuePricesJob::class, fn ($job) => $job->days === 30);
        $this->artisan('grs:sync-prices --days=0')->assertExitCode(1);
        Bus::assertDispatchedTimes(SyncGrsDuePricesJob::class, 1);
    }

    public function test_fifty_overdue_hotels_dispatch_in_ten_hotel_batches_without_claim_or_priority_changes(): void
    {
        $this->createScheduleFixture();
        Bus::fake();
        Http::fake();

        $service = app(GrsPriceRefreshScheduleService::class);
        $this->assertSame('shared_ssp', (new HotelPriceRefreshSchedule())->getConnectionName());
        $before = DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')
            ->orderBy('id')->pluck('next_gds_run_at', 'id')->all();

        (new SyncGrsDuePricesJob())->handle($service);
        Bus::assertDispatchedTimes(RefreshGrsPropertyPricesJob::class, 10);
        $this->assertSame(
            array_map('strval', range(1, 10)),
            Bus::dispatched(RefreshGrsPropertyPricesJob::class)->pluck('grsId')->all()
        );
        Bus::assertDispatched(RefreshGrsPropertyPricesJob::class, fn ($job) => $job->days === 120);
        $this->assertSame($before, DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')
            ->orderBy('id')->pluck('next_gds_run_at', 'id')->all());
        Http::assertNothingSent();

        // Once the first ten have *actually persisted*, the next scan picks
        // the next ten oldest due hotels, not arbitrary later entries.
        $repository = app(HotelPriceRefreshScheduleRepository::class);
        foreach (range(1, 10) as $id) {
            $repository->markPersisted($id, (string) $id);
        }
        Bus::fake();
        (new SyncGrsDuePricesJob())->handle($service);
        Bus::assertDispatchedTimes(RefreshGrsPropertyPricesJob::class, 10);
        $this->assertSame(
            array_map('strval', range(11, 20)),
            Bus::dispatched(RefreshGrsPropertyPricesJob::class)->pluck('grsId')->all()
        );
    }

    public function test_start_and_http_200_are_recorded_without_advancing_due_time_until_persisted(): void
    {
        $this->createScheduleFixture();
        $repository = app(HotelPriceRefreshScheduleRepository::class);
        $before = DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')
            ->where('id', 1)->value('next_gds_run_at');

        $repository->markRequestStarted(1, '1');
        $row = DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->where('id', 1)->first();
        $this->assertNotNull($row->last_gds_success_run_at);
        $this->assertNull($row->last_gds_success_at);
        $this->assertSame($before, $row->next_gds_run_at);

        $repository->markHttp200(1, '1');
        $row = DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->where('id', 1)->first();
        $this->assertNotNull($row->last_gds_success_at);
        $this->assertSame($before, $row->next_gds_run_at);

        $this->assertSame(10, $repository->markPersisted(1, '1'));
        $this->assertGreaterThan(now()->toDateTimeString(), DB::connection('shared_ssp')
            ->table('hotel_price_refresh_schedules')->where('id', 1)->value('next_gds_run_at'));
    }

    public function test_200_triggers_both_tracking_hooks_even_when_response_has_no_calendar_rows(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();
        RateLimiter::clear('grs-availability');
        Http::fake(['*/v1/available-rooms*' => Http::response(['value' => ['rooms' => []]], 200)]);
        $provider = new Provider([
            'code' => 'grs', 'config' => [
                'base_url' => 'https://example.test', 'token' => 'token',
                'availability_rate_limit' => ['max_requests' => 10, 'window_minutes' => 1],
            ],
        ]);
        $started = 0;
        $ok = 0;
        $adapter = new RateLimitedGrsAdapter($provider);
        $adapter->trackAvailability(
            function () use (&$started): void { $started++; },
            function () use (&$ok): void { $ok++; }
        );
        $this->assertTrue($adapter->fetchAvailability('1', CarbonImmutable::today(), CarbonImmutable::tomorrow())->isEmpty());
        $this->assertSame(1, $started);
        $this->assertSame(1, $ok);
        Http::assertSentCount(1);
    }

    public function test_availability_and_extra_room_request_each_consume_one_api_slot(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();
        RateLimiter::clear('grs-availability');
        Http::fake(['*/v1/available-rooms*' => Http::response(['value' => ['rooms' => []]], 200)]);
        $provider = new Provider([
            'code' => 'grs', 'config' => [
                'base_url' => 'https://example.test', 'token' => 'test-token',
                'availability_rate_limit' => ['max_requests' => 1, 'window_minutes' => 1],
            ],
        ]);
        $adapter = new RateLimitedGrsAdapter($provider);
        $adapter->fetchAvailability('5', CarbonImmutable::today(), CarbonImmutable::tomorrow());

        try {
            $adapter->fetchRoomTypes('5');
            $this->fail('Supplemental call should be deferred when the API slot is consumed.');
        } catch (GrsApiQuotaExceeded $e) {
            $this->assertGreaterThan(0, $e->retryAfterSeconds);
        }
        Http::assertSentCount(1);
        $this->assertInstanceOf(GrsApiQuotaExceeded::class, $adapter->supplementalError);
    }

    private function createScheduleFixture(): void
    {
        config([
            'database.connections.shared_ssp' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'cache.default' => 'array',
        ]);
        DB::purge('shared_ssp');
        Cache::flush();

        Schema::dropIfExists('accommodation_provider_maps');
        Schema::dropIfExists('providers');
        Schema::create('providers', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->boolean('is_active');
            $table->boolean('is_online');
            $table->text('config')->nullable();
            $table->timestamps();
        });
        Schema::create('accommodation_provider_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('accommodation_id');
            $table->string('provider_property_id');
            $table->timestamps();
        });
        Schema::connection('shared_ssp')->create('hotel_price_refresh_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hotel_accommodation_id');
            $table->string('grs_id')->nullable();
            $table->timestamp('next_gds_run_at')->nullable();
            $table->timestamp('last_gds_success_run_at')->nullable();
            $table->timestamp('last_gds_success_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('refresh_interval_minutes')->default(10);
        });
        DB::table('providers')->insert([
            'id' => 1, 'code' => 'grs', 'is_active' => 1, 'is_online' => 1,
            'config' => json_encode([
                'availability_rate_limit' => ['max_requests' => 10, 'window_minutes' => 1],
                'price_refresh' => ['default_days' => 120],
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $maps = [];
        $rows = [];
        foreach (range(1, 50) as $id) {
            $maps[] = [
                'provider_id' => 1, 'accommodation_id' => $id,
                'provider_property_id' => (string) $id,
                'created_at' => now(), 'updated_at' => now(),
            ];
            $rows[] = [
                'id' => $id, 'hotel_accommodation_id' => $id + 1000,
                'grs_id' => (string) $id,
                'next_gds_run_at' => now()->subMinutes(51 - $id)->toDateTimeString(),
                'is_active' => 1, 'refresh_interval_minutes' => 10,
            ];
        }
        DB::table('accommodation_provider_maps')->insert($maps);
        DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->insert($rows);
    }
}
