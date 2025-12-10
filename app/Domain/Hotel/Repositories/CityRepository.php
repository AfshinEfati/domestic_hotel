<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\City;
use App\Models\Country;
use App\Models\Provider;
use App\Models\ProviderCityMap;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class CityRepository
{
    public function upsertFromProvider(array $payload, Provider $provider): City
    {
        $country = Country::query()->firstOrCreate(
            ['iso2' => $payload['country_code_alpha_2'] ?? 'IR'],
            [
                'fa_name' => $payload['country_name'] ?? 'ایران',
                'en_name' => $payload['country_name_en'] ?? 'Iran',
                'iso3'    => $payload['country_code_alpha_3'] ?? 'IRN',
            ]
        );

        $state = null;
        if (!empty($payload['province_name'])) {
            $state = State::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'fa_name'    => $payload['province_name'],
                ],
                [
                    'en_name' => $payload['province_name_en'] ?? null,
                ]
            );
        }

        $city = City::firstOrCreate(
            [
                'country_id' => $country->id,
                'fa_name'    => $payload['name'],
            ],
            [
                'en_name'    => $payload['name_en'] ?? null,
                'state_id'   => $state?->id,
                'is_active'  => true,
                'is_popular' => false,
            ]
        );

        ProviderCityMap::updateOrCreate(
            [
                'provider_id'      => $provider->id,
                'provider_city_id' => (string)($payload['id']),
            ],
            [
                'city_id'  => $city->id,
                'fa_name'  => $payload['name'],
                'en_name'  => $payload['name_en'] ?? null,
            ]
        );

        return $city;
    }
}
