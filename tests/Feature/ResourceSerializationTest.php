<?php

namespace Tests\Feature;

use App\Http\Resources\AccommodationResource;
use App\Http\Resources\AccommodationTypeResource;
use App\Models\Accommodation;
use App\Models\AccommodationType;
use App\Models\Facility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourceSerializationTest extends TestCase
{
    public function test_accommodation_resource_facilities_serializes_as_array(): void
    {
        $now = Carbon::now();

        $facilities = collect([
            new Facility([
                'id' => 1,
                'facility_group_id' => 10,
                'fa_name' => 'Pool Fa',
                'en_name' => 'Pool',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            new Facility([
                'id' => 2,
                'facility_group_id' => 20,
                'fa_name' => 'Sauna Fa',
                'en_name' => 'Sauna',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
        ]);

        $accommodation = new Accommodation([
            'id' => 100,
            'city_id' => 200,
            'fa_name' => 'Sample Fa Hotel',
            'en_name' => 'Sample Hotel',
            'accommodation_type_id' => 300,
            'star' => 4,
            'grade' => 'A',
            'address' => '123 Sample Street',
            'lat' => '35.0',
            'lng' => '51.0',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $accommodation->setRelation('facilities', $facilities);

        $resource = new AccommodationResource($accommodation);
        $result = $resource->response(new Request())->getData(true);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('facilities', $result['data']);
        $this->assertIsArray($result['data']['facilities']);
        $this->assertCount(2, $result['data']['facilities']);
    }

    public function test_accommodation_type_resource_accommodations_serializes_as_array(): void
    {
        $now = Carbon::now();

        $accommodations = collect([
            (new Accommodation([
                'id' => 101,
                'city_id' => 201,
                'fa_name' => 'First Fa Hotel',
                'en_name' => 'Hotel One',
                'accommodation_type_id' => 301,
                'star' => 5,
                'grade' => 'A+',
                'address' => 'First Avenue',
                'lat' => '36.0',
                'lng' => '52.0',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]))->setRelation('facilities', collect()),
            (new Accommodation([
                'id' => 102,
                'city_id' => 202,
                'fa_name' => 'Second Fa Hotel',
                'en_name' => 'Hotel Two',
                'accommodation_type_id' => 302,
                'star' => 3,
                'grade' => 'B',
                'address' => 'Second Avenue',
                'lat' => '37.0',
                'lng' => '53.0',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]))->setRelation('facilities', collect()),
        ]);

        $type = new AccommodationType([
            'id' => 400,
            'fa_name' => 'Hotel Fa',
            'en_name' => 'Hotel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $type->setRelation('accommodations', $accommodations);

        $resource = new AccommodationTypeResource($type);
        $result = $resource->response(new Request())->getData(true);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('accommodations', $result['data']);
        $this->assertIsArray($result['data']['accommodations']);
        $this->assertCount(2, $result['data']['accommodations']);
    }
}
