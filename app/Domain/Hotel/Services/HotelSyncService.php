<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Repositories\AccommodationRepository;
use App\Domain\Hotel\Repositories\RoomCalendarRepository;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use App\Services\HotelDataSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

readonly class HotelSyncService
{
    public function __construct(
        private CityRepository $cityRepo,
        private AccommodationRepository $accRepo,
        private RoomCalendarRepository $calendarRepo,
        private HotelDataSyncService $dataSyncService,
        private AccommodationProviderMapRepositoryInterface $accommodationMaps,
        private RoomTypeProviderMapRepositoryInterface $roomMaps,
        private RatePlanProviderMapRepositoryInterface $rateMaps,
    ) {
    }

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
        // Resolve the mapped city once, not once per provider hotel.
        $cityId = $this->cityRepo->mappedCityId((int) $provider->id, $providerCityId);
        $city = $cityId === null ? null : $this->cityRepo->find($cityId);
        if ($city === null) {
            return;
        }

        $page = 1;
        do {
            $items = $adapter->fetchPropertiesByCity($providerCityId, $page, 200);
            if ($items->isEmpty()) {
                break;
            }

            foreach ($items as $accData) {
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

        $map = $this->accommodationMaps->findForProviderProperty((int) $provider->id, $providerPropertyId);
        if ($map === null) {
            return;
        }

        $accId = (int) $map->accommodation_id;
        $roomTypeMaps = $this->loadRoomTypeMaps((int) $provider->id, $availability);
        $ratePlanMaps = $this->loadRatePlanMaps((int) $provider->id, $availability);
        [$roomTypeMaps, $ratePlanMaps] = $this->ensureAvailabilityMappings(
            $provider, $adapter, $map, $availability, $roomTypeMaps, $ratePlanMaps
        );

        $availability->groupBy(fn (array $row) => ($row['room_type_id'] ?? '') . '#' . ($row['rate_plan_id'] ?? ''))
            ->each(function (Collection $rows) use (
                $provider, $accId, $providerPropertyId, $roomTypeMaps, $ratePlanMaps
            ) {
                $first = $rows->first();
                if (!is_array($first)) {
                    return;
                }

                $providerRoomTypeId = (string) ($first['room_type_id'] ?? '');
                $providerRatePlanId = (string) ($first['rate_plan_id'] ?? '');
                if ($providerRoomTypeId === '' || $providerRatePlanId === '') {
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

                // Persist every valid date supplied by the provider, even outside
                // the requested check-in/check-out range. No synthetic days are added.
                $this->calendarRepo->bulkUpsert(
                    (int) $provider->id,
                    $accId,
                    (int) $roomTypeMap->room_type_id,
                    (int) $ratePlanMap->rate_plan_id,
                    $providerPropertyId,
                    $providerRoomTypeId,
                    $providerRatePlanId,
                    $normalized
                );
            });
    }

    /** @return array{0: Collection, 1: Collection} */
    private function ensureAvailabilityMappings(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        AccommodationProviderMap $map,
        Collection $availability,
        Collection $roomTypeMaps,
        Collection $ratePlanMaps
    ): array {
        $missingRoomTypeIds = $this->missingProviderIds($availability, 'room_type_id', $roomTypeMaps);
        $missingRatePlanIds = $this->missingProviderIds($availability, 'rate_plan_id', $ratePlanMaps);

        if ($missingRoomTypeIds->isEmpty() && $missingRatePlanIds->isEmpty()) {
            return [$roomTypeMaps, $ratePlanMaps];
        }

        $this->syncRoomTypesFromProvider($provider, $adapter, $map);

        return [
            $this->loadRoomTypeMaps((int) $provider->id, $availability),
            $this->loadRatePlanMaps((int) $provider->id, $availability),
        ];
    }

    private function syncRoomTypesFromProvider(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        AccommodationProviderMap $map
    ): void {
        try {
            $roomTypes = $adapter->fetchRoomTypes($map->provider_property_id);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch provider room types', [
                'provider_id' => $provider->id,
                'property_id' => $map->provider_property_id,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($roomTypes === null || $roomTypes->isEmpty()) {
            return;
        }

        $this->dataSyncService->syncRoomTypes($map, $roomTypes->all());
    }

    private function missingProviderIds(Collection $availability, string $key, Collection $existingMaps): Collection
    {
        return $this->extractProviderIds($availability, $key)
            ->reject(fn (string $id) => $existingMaps->has($id))
            ->values();
    }

    /** @return Collection<int, string> */
    private function extractProviderIds(Collection $availability, string $key): Collection
    {
        return $availability
            ->pluck($key)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    private function loadRoomTypeMaps(int $providerId, Collection $availability): Collection
    {
        $roomTypeIds = $this->extractProviderIds($availability, 'room_type_id')->all();
        return $this->roomMaps->mappedForProviderIds($providerId, $roomTypeIds);
    }

    private function loadRatePlanMaps(int $providerId, Collection $availability): Collection
    {
        $ratePlanIds = $this->extractProviderIds($availability, 'rate_plan_id')->all();
        return $this->rateMaps->mappedForProviderIds($providerId, $ratePlanIds);
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
                    'cta' => (bool) ($row['cta'] ?? false),
                    'ctd' => (bool) ($row['ctd'] ?? false),
                    'closed' => (bool) ($row['closed'] ?? false),
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
            return (int) $value;
        }
        return null;
    }
}
