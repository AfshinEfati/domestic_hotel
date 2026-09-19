<?php

namespace Tests\Feature;

use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Jobs\Hotel\SyncCitiesJob;
use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob;
use App\Jobs\Hotel\V2\SyncGrsDuePricesJob;
use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob;
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
    public function test_missing_config_uses_defaults_and_valid_admin_values_override_them(): void
    {
        $provider = new Provider(['config' => []]);
        $this->assertSame(GrsRefreshSettings::defaults(), GrsRefreshSettings::from($provider));
        $provider->config = [
            'price_refresh' => [
                'default_days' => 120,
                'dispatch_limit' => 1,
                'claim_minutes' => 25,
                'failure_backoff_minutes' => 35,
                'api_cooldown_minutes' => 45,
                'scheduler_enabled' => true,
            ],
        ];
        $this->assertSame([
            'default_days' => 120,
            'dispatch_limit' => 1,
            'claim_minutes' => 25,
            'failure_backoff_minutes' => 35,
            'api_cooldown_minutes' => 45,
            'scheduler_enabled' => true,
        ], GrsRefreshSettings::from($provider));
        $provider->config = ['price_refresh' => ['dispatch_limit' => 0, 'scheduler_enabled' => false]];
        $this->assertSame(10, GrsRefreshSettings::from($provider)['dispatch_limit']);
        $this->assertFalse(GrsRefreshSettings::from($provider)['scheduler_enabled']);
    }

    public function test_command_is_independent_and_defaults_to_ninety_days(): void
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

    public function test_due_selector_claims_oldest_shared_row_first_without_http(): void
    {
        Bus::fake();
        Http::fake();
        config([
            'database.connections.shared_ssp' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'cache.default' => 'array',
        ]);
        DB::purge('shared_ssp');
        Cache::flush();

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
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('refresh_interval_minutes')->default(10);
        });
        DB::table('providers')->insert([
            'id' => 1, 'code' => 'grs', 'is_active' => 1, 'is_online' => 1,
            'config' => json_encode(['price_refresh' => ['dispatch_limit' => 1, 'default_days' => 120]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['100', '200'] as $id) {
            DB::table('accommodation_provider_maps')->insert([
                'provider_id' => 1, 'accommodation_id' => (int) $id,
                'provider_property_id' => $id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->insert([
            ['id' => 1, 'hotel_accommodation_id' => 998, 'grs_id' => '100',
                'next_gds_run_at' => now()->subHour(), 'is_active' => 1, 'refresh_interval_minutes' => 10],
            ['id' => 2, 'hotel_accommodation_id' => 999, 'grs_id' => '200',
                'next_gds_run_at' => now()->subHours(2), 'is_active' => 1, 'refresh_interval_minutes' => 15],
        ]);

        (new SyncGrsDuePricesJob())->handle();

        Bus::assertDispatchedTimes(RefreshGrsPropertyPricesJob::class, 1);
        Bus::assertDispatched(RefreshGrsPropertyPricesJob::class, fn ($job) =>
            $job->grsId === '200' && $job->days === 120 && $job->intervalMinutes === 15);
        $this->assertGreaterThan(
            now()->toDateTimeString(),
            DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->where('id', 2)->value('next_gds_run_at')
        );
        $this->assertLessThan(
            now()->toDateTimeString(),
            DB::connection('shared_ssp')->table('hotel_price_refresh_schedules')->where('id', 1)->value('next_gds_run_at')
        );
        Http::assertNothingSent();
    }

    public function test_availability_and_extra_room_request_each_consume_one_api_slot(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();
        RateLimiter::clear('grs-availability');
        Http::fake(['*/v1/available-rooms*' => Http::response(['value' => ['rooms' => []]], 200)]);
        $provider = new Provider([
            'code' => 'grs',
            'config' => [
                'base_url' => 'https://example.test', 'token' => 'test-token',
                'availability_rate_limit' => ['max_requests' => 1, 'window_minutes' => 1],
            ],
        ]);
        $adapter = new RateLimitedGrsAdapter($provider);
        $adapter->fetchAvailability('5', CarbonImmutable::today(), CarbonImmutable::tomorrow());

        try {
            $adapter->fetchRoomTypes('5');
            $this->fail('Supplemental call should be deferred when the only API slot is consumed.');
        } catch (GrsApiQuotaExceeded $e) {
            $this->assertGreaterThan(0, $e->retryAfterSeconds);
        }
        Http::assertSentCount(1);
        $this->assertInstanceOf(GrsApiQuotaExceeded::class, $adapter->supplementalError);
    }
}
