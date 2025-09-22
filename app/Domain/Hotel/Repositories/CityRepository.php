<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\City;
use App\Models\Country;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;

class CityRepository
{
    public function upsertFromProvider(array $payload, Provider $provider): City
    {
        $country = Country::firstOrCreate(
            ['id' => $payload['country_id'] ?? 1],
            ['fa_name' => 'ایران', 'en_name' => 'Iran']
        );

        $city = City::updateOrCreate(
            [
                'country_id' => $country->id,
                'fa_name' => $payload['fa_name'],
            ],
            [
                'en_name' => $payload['en_name'] ?? null,
                'lat' => $payload['lat'] ?? null,
                'lng' => $payload['lng'] ?? null,
            ]
        );

        DB::table('provider_city_maps')->updateOrInsert(
            [
                'provider_id' => $provider->id,
                'provider_city_id' => (string)$payload['id'],
            ],
            [
                'city_id' => $city->id,
                'fa_name' => $payload['fa_name'] ?? null,
                'en_name' => $payload['en_name'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $city;
    }
}
