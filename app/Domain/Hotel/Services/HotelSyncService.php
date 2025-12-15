<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Repositories\AccommodationRepository;
use App\Domain\Hotel\Repositories\RoomCalendarRepository;
use App\Models\Provider;
use App\Models\RatePlanProviderMap;
use App\Models\RoomTypeProviderMap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        if ($availability->isEmpty()) {
            return;
        }

        $accId = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->where('provider_property_id', $providerPropertyId)
            ->value('accommodation_id');

        if (!$accId) {
            return;
        }

        $roomTypeMaps = $this->loadRoomTypeMaps($provider->id, $availability);
        $ratePlanMaps = $this->loadRatePlanMaps($provider->id, $availability);

        $availability->groupBy(fn(array $row) => ($row['room_type_id'] ?? '') . '#' . ($row['rate_plan_id'] ?? ''))
            ->each(function (Collection $rows) use (
                $provider,
                $accId,
                $providerPropertyId,
                $roomTypeMaps,
                $ratePlanMaps
            ) {
                $first = $rows->first();
                if (!is_array($first)) {
                    return;
                }

                $providerRoomTypeId = (string)($first['room_type_id'] ?? '');
                $providerRatePlanId = (string)($first['rate_plan_id'] ?? '');

                if ($providerRoomTypeId === '' || $providerRatePlanId === '') {
                    Log::warning('Availability row missing provider identifiers', [
                        'provider_id' => $provider->id,
                        'property_id' => $providerPropertyId,
                    ]);
                    return;
                }

                $roomTypeMap = $roomTypeMaps->get($providerRoomTypeId);
                $ratePlanMap = $ratePlanMaps->get($providerRatePlanId);

                if (!$roomTypeMap || !$ratePlanMap) {
                    Log::warning('Skipping availability rows without provider mappings', [
                        'provider_id' => $provider->id,
                        'property_id' => $providerPropertyId,
                        'provider_room_type_id' => $providerRoomTypeId,
                        'provider_rate_plan_id' => $providerRatePlanId,
                    ]);
                    return;
                }

                $normalized = $this->normalizeAvailabilityRows($rows);
                if ($normalized->isEmpty()) {
                    return;
                }

                $this->calendarRepo->bulkUpsert(
                    $provider->id,
                    (int)$accId,
                    (int)$roomTypeMap->room_type_id,
                    (int)$ratePlanMap->rate_plan_id,
                    $providerPropertyId,
                    $providerRoomTypeId,
                    $providerRatePlanId,
                    $normalized
                );
            });
    }

    private function loadRoomTypeMaps(int $providerId, Collection $availability): Collection
    {
        $roomTypeIds = $availability
            ->pluck('room_type_id')
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (string)$id)
            ->unique();

        if ($roomTypeIds->isEmpty()) {
            return collect();
        }

        return RoomTypeProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereIn('provider_room_type_id', $roomTypeIds)
            ->get()
            ->keyBy('provider_room_type_id');
    }

    private function loadRatePlanMaps(int $providerId, Collection $availability): Collection
    {
        $ratePlanIds = $availability
            ->pluck('rate_plan_id')
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (string)$id)
            ->unique();

        if ($ratePlanIds->isEmpty()) {
            return collect();
        }

        return RatePlanProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereIn('provider_rate_plan_id', $ratePlanIds)
            ->get()
            ->keyBy('provider_rate_plan_id');
    }

    private function normalizeAvailabilityRows(Collection $rows): Collection
    {
        return $rows
            ->map(function (array $row) {
                $day = $row['day'] ?? null;
                if (!$day) {
                    return null;
                }

                try {
                    $day = CarbonImmutable::parse($day)->format('Y-m-d');
                } catch (\Throwable) {
                    return null;
                }

                return [
                    'day' => $day,
                    'rack_rate' => $this->toNullableInt($row['rack_rate'] ?? null),
                    'daily_rate' => $this->toNullableInt($row['daily_rate'] ?? null),
                    'grs_rate' => $this->toNullableInt($row['grs_rate'] ?? null),
                    'min_stay' => $this->toNullableInt($row['min_stay'] ?? null),
                    'max_stay' => $this->toNullableInt($row['max_stay'] ?? null),
                    'cta' => (bool)($row['cta'] ?? false),
                    'ctd' => (bool)($row['ctd'] ?? false),
                    'closed' => (bool)($row['closed'] ?? false),
                    'inventory' => $this->toNullableInt($row['inventory'] ?? null),
                ];
            })
            ->filter()
            ->values();
    }

    private function toNullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}
