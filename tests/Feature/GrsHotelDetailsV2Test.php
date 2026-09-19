<?php

namespace Tests\Feature;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository;
use App\Domain\Hotel\V2\GrsHotelDetailsClient;
use App\Jobs\Hotel\V2\SyncGrsDuePricesJob;
use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob;
use App\Jobs\Hotel\V2\SyncGrsHotelDetailsJob;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GrsHotelDetailsV2Test extends TestCase
{
    public function test_command_queues_only_details_jobs_six_seconds_apart(): void
    {
        config(['queue.default' => 'redis', 'cache.default' => 'database']);
        Bus::fake();
        Http::fake();

        $provider = new Provider(['id' => 9, 'code' => 'grs', 'is_active' => true, 'is_online' => true]);
        $maps = collect([
            new AccommodationProviderMap(['id' => 11, 'provider_id' => 9, 'accommodation_id' => 101, 'provider_property_id' => '2065']),
            new AccommodationProviderMap(['id' => 12, 'provider_id' => 9, 'accommodation_id' => 102, 'provider_property_id' => '2066']),
        ]);
        $repository = Mockery::mock(GrsHotelDetailsRepository::class);
        $repository->shouldReceive('grsProvider')->once()->andReturn($provider);
        $repository->shouldReceive('mappedHotels')->once()->with(9)->andReturn($maps);
        $this->app->instance(GrsHotelDetailsRepository::class, $repository);

        $this->artisan('grs:sync-details')->assertExitCode(0);
        Bus::assertDispatchedTimes(SyncGrsHotelDetailsJob::class, 2);
        Bus::assertNotDispatched(SyncGrsDuePricesJob::class);
        Bus::assertNotDispatched(SyncGrsHotelCatalogJob::class);
        Http::assertNothingSent();

        $jobs = Bus::dispatched(SyncGrsHotelDetailsJob::class)->values();
        $this->assertSame('grs-details', $jobs[0]->queue);
        $this->assertSame(11, $jobs[0]->mapId);
        $this->assertSame(12, $jobs[1]->mapId);
        $this->assertSame(6, (int) $jobs[0]->delay->diffInSeconds($jobs[1]->delay));
        $this->assertSame(['grs-details'], config('horizon.defaults.supervisor-grs-details.queue'));
        $this->assertNotContains('grs-details', config('horizon.defaults.supervisor-1.queue'));
    }

    public function test_client_fetches_full_property_including_rules_facilities_and_rooms(): void
    {
        $provider = new Provider([
            'code' => 'grs', 'is_active' => true, 'is_online' => true,
            'config' => ['base_url' => 'https://grs.example', 'token' => 'test-token'],
        ]);
        Http::fake(['https://grs.example/v1/properties/2065' => Http::response([
            'code' => 200,
            'value' => ['property' => [
                'id' => 2065,
                'facilities' => [['id' => 13, 'name' => 'وای‌فای', 'group_name' => 'تجهیزات']],
                'rules' => [['id' => 99, 'category' => 'children', 'conditions' => ['max_child_age' => 8]]],
                'room_types' => [['id' => 422875, 'name' => 'اتاق سه تخته', 'rate_plans' => [['id' => 2104, 'name' => 'اقامت صبحانه']]]],
            ]],
        ], 200)]);

        $property = app(GrsHotelDetailsClient::class)->fetch($provider, '2065');
        $this->assertSame(2065, $property['id']);
        $this->assertCount(1, $property['facilities']);
        $this->assertCount(1, $property['rules']);
        $this->assertSame(2104, $property['room_types'][0]['rate_plans'][0]['id']);
        Http::assertSent(fn ($request) => $request->hasHeader('Client-Token', 'test-token'));
    }

    public function test_client_rejects_details_for_a_different_property(): void
    {
        $provider = new Provider([
            'code' => 'grs', 'is_active' => true, 'is_online' => true,
            'config' => ['base_url' => 'https://grs.example', 'token' => 'test-token'],
        ]);
        Http::fake(['*' => Http::response([
            'code' => 200,
            'value' => ['property' => ['id' => 5, 'facilities' => [], 'rules' => [], 'room_types' => [['id' => 97]]]],
        ], 200)]);

        $this->expectException(RuntimeException::class);
        app(GrsHotelDetailsClient::class)->fetch($provider, '2065');
    }
}
