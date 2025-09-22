<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\Accommodation;
use App\Models\City;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;

class AccommodationRepository
{
    public function upsertFromProvider(
        City $city,
        array $payload,
        Provider $provider
    ): Accommodation {
        $acc = Accommodation::updateOrCreate(
            [
                'city_id' => $city->id,
                'fa_name' => $payload['fa_name'],
            ],
            [
                'en_name' => $payload['en_name'] ?? null,
                'address' => $payload['address'] ?? null,
                'lat' => $payload['lat'] ?? null,
                'lng' => $payload['lng'] ?? null,
                'star' => $payload['star'] ?? null,
                'is_active' => true,
            ]
        );

        DB::table('accommodation_provider_maps')->updateOrInsert(
            [
                'provider_id' => $provider->id,
                'provider_property_id' => (string)$payload['property_id'],
            ],
            [
                'accommodation_id' => $acc->id,
                'fa_name' => $payload['fa_name'] ?? null,
                'en_name' => $payload['en_name'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $acc;
    }
}
