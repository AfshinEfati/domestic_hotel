<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Repositories\AccommodationRepository;
use App\Domain\Hotel\Repositories\RoomCalendarRepository;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class HotelSyncService
{
    public function __construct(
        private readonly CityRepository $cityRepo,
        private readonly AccommodationRepository $accRepo,
        private readonly RoomCalendarRepository $calendarRepo,
    ) {}

    public function syncCities(Provider $provider, ProviderAdapterInterface $adapter): void
    {
        $adapter->fetchCities()->each(function (array $c) use ($provider) {
            $this->cityRepo->upsertFromProvider($c, $provider);
        });
    }

    public function syncPropertiesForCity(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerCityId
    ): void {
        $page = 1;
        do {
            $items = $adapter->fetchPropertiesByCity($providerCityId, $page, 200);
            if ($items->isEmpty()) {
                break;
            }

            foreach ($items as $accData) {
                $cityId = DB::table('provider_city_maps')
                    ->where('provider_id', $provider->id)
                    ->where('provider_city_id', $providerCityId)
                    ->value('city_id');

                if (!$cityId) {
                    continue;
                }

                $city = \App\Models\City::find($cityId);
                $this->accRepo->upsertFromProvider($city, $accData, $provider);
            }
            $page++;
        } while ($items->count() === 200);
    }

    public function crawlAvailabilityForProperty(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerPropertyId,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): void {
        $availability = $adapter->fetchAvailability($providerPropertyId, $from, $to);

        $accId = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->where('provider_property_id', $providerPropertyId)
            ->value('accommodation_id');

        if (!$accId) {
            return;
        }

        $byKey = $availability->groupBy(function ($r) {
            return ($r['room_type_id'] ?? '') . '#' . ($r['rate_plan_id'] ?? '');
        });

        foreach ($byKey as $rows) {
            // TODO: map room_type_id و rate_plan_id داخلی مثل قبل
            // این بخش رو وقتی Adapter واقعی پیاده شد تکمیل می‌کنیم
        }
    }
}
