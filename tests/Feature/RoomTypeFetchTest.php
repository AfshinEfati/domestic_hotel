<?php

namespace Tests\Feature;

use App\Jobs\DispatchRoomTypeFetchJobs;
use App\Jobs\FetchRoomTypesFromProviderJob;
use App\Models\Accommodation;
use App\Models\AccommodationProviderMap;
use App\Models\City;
use App\Models\Provider;
use App\Models\RoomType;
use App\Models\RoomTypeName;
use App\Models\RoomTypeProviderMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\TestProviderAdapter;
use Tests\TestCase;

class RoomTypeFetchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_dispatches_jobs_for_active_providers()
    {
        Queue::fake();

        $provider = Provider::factory()->create(['is_active' => true]);
        $city = City::factory()->create();
        $accommodation = Accommodation::factory()->create(['city_id' => $city->id]);

        AccommodationProviderMap::create([
            'accommodation_id' => $accommodation->id,
            'provider_id' => $provider->id,
            'provider_property_id' => 'PROP-001',
            'fa_name' => 'Hotel Test',
        ]);

        DispatchRoomTypeFetchJobs::dispatch();

        Queue::assertPushed(FetchRoomTypesFromProviderJob::class);
    }

    /** @test */
    public function it_fetches_and_normalizes_room_types()
    {
        $provider = Provider::factory()->create([
            'is_active' => true,
            'class' => TestProviderAdapter::class,
        ]);
        $city = City::factory()->create();
        $accommodation = Accommodation::factory()->create(['city_id' => $city->id]);

        $map = AccommodationProviderMap::create([
            'accommodation_id' => $accommodation->id,
            'provider_id' => $provider->id,
            'provider_property_id' => 'PROP-001',
            'fa_name' => 'Hotel Test',
        ]);

        $job = new FetchRoomTypesFromProviderJob($map);
        $job->handle(app(\App\Services\HotelDataSyncService::class));

        // Check RoomTypeName
        $this->assertDatabaseHas('room_type_names', ['fa_name' => 'Single Room']);
        $this->assertDatabaseHas('room_type_names', ['fa_name' => 'Double Room']);

        $singleName = RoomTypeName::where('fa_name', 'Single Room')->first();

        // Check RoomType
        $this->assertDatabaseHas('room_types', [
            'accommodation_id' => $accommodation->id,
            'fa_name' => '  Single   Room  ',
            'room_type_name_id' => $singleName->id,
        ]);

        // Check RatePlans
        $this->assertDatabaseHas('rate_plans', [
            'accommodation_id' => $accommodation->id,
            'fa_name' => 'Breakfast Included'
        ]);

        $this->assertDatabaseHas('rate_plan_provider_maps', [
            'provider_id' => $provider->id,
            'provider_rate_plan_id' => 'RP-101',
        ]);
    }
}
