<?php

namespace Tests\Unit;

use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripAvailabilityRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class SnappTripAvailabilityMappingTest extends TestCase
{
    public function test_missing_room_mappings_are_detected_in_one_batch(): void
    {
        Schema::dropIfExists('room_type_provider_maps');
        Schema::create('room_type_provider_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('room_type_id');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('accommodation_provider_map_id');
            $table->string('provider_room_type_id');
            $table->string('fa_name')->nullable();
            $table->string('en_name')->nullable();
            $table->timestamps();
        });

        DB::table('room_type_provider_maps')->insert([
            'room_type_id' => 100,
            'provider_id' => 4,
            'accommodation_provider_map_id' => 11,
            'provider_room_type_id' => '28936',
            'fa_name' => 'Existing room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provider = new Provider();
        $provider->forceFill(['id' => 4]);
        $provider->exists = true;

        $map = new AccommodationProviderMap();
        $map->forceFill([
            'id' => 11,
            'provider_id' => 4,
            'provider_property_id' => '2389',
        ]);
        $map->exists = true;

        $catalog = (new ReflectionClass(SnappTripCatalogRepository::class))
            ->newInstanceWithoutConstructor();
        $repository = new SnappTripAvailabilityRepository($catalog);

        $this->assertSame(
            ['28937', '28938'],
            $repository->missingRoomMappings(
                $provider,
                $map,
                ['28936', '28937', 28938, '28937', ''],
            ),
        );
    }
}
