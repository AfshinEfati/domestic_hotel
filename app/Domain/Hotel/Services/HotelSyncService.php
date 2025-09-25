<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\AccommodationRepository;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Repositories\RoomCalendarRepository;
use App\Support\Logging\SystemLogger;
use App\Models\Provider;
use App\Models\RatePlan;
use App\Models\RatePlanProviderMap;
use App\Models\RoomType;
use App\Models\RoomTypeProviderMap;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;


class HotelSyncService
{
    private const ALLOWED_MEAL_TYPES = ['breakfast', 'half_board', 'full_board'];
    private const ALLOWED_FOOD_BOARD_TYPES = ['limit_options', 'full_options'];

    public function __construct(
        private readonly CityRepository $cityRepo,
        private readonly AccommodationRepository $accRepo,
        private readonly RoomCalendarRepository $calendarRepo,
        private readonly SystemLogger $logger,

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

        $this->logger->info(__METHOD__, 'Availability fetched from provider', [
            'provider_id' => $provider->id,
            'provider_property_id' => $providerPropertyId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $availability->count(),
        ]);
        if ($availability->isEmpty()) {
            return;
        }

        $accId = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->where('provider_property_id', $providerPropertyId)
            ->value('accommodation_id');

        if (!$accId) {
            $this->logger->warning(__METHOD__, 'Accommodation mapping missing for provider property', [

                'provider_id' => $provider->id,
                'provider_property_id' => $providerPropertyId,
            ]);
            return;
        }

        $roomTypeDefinitions = null;
        $ratePlanDefinitions = null;

        $roomTypeMaps = $this->loadRoomTypeMaps($provider->id, $availability);
        $ratePlanMaps = $this->loadRatePlanMaps($provider->id, $availability);

        $availability->groupBy(fn(array $row) => ($row['room_type_id'] ?? '') . '#' . ($row['rate_plan_id'] ?? ''))
            ->each(function (Collection $rows) use (
                $provider,
                $accId,
                $providerPropertyId,
                $roomTypeMaps,
                $ratePlanMaps,
                &$roomTypeDefinitions,
                &$ratePlanDefinitions,
                $adapter
            ) {
                $first = $rows->first();
                if (!is_array($first)) {
                    return;
                }

                $providerRoomTypeId = (string)($first['room_type_id'] ?? '');
                $providerRatePlanId = (string)($first['rate_plan_id'] ?? '');

                if ($providerRoomTypeId === '' || $providerRatePlanId === '') {
                    $this->logger->warning(__METHOD__, 'Availability row missing provider identifiers', [
                        'provider_id' => $provider->id,
                        'provider_property_id' => $providerPropertyId,
                        'row_sample' => $first,
                    ]);
                    return;
                }

                $roomTypeMap = $roomTypeMaps->get($providerRoomTypeId);
                if (!$roomTypeMap) {
                    $roomTypeMap = $this->ensureRoomTypeMap(
                        $provider,
                        $adapter,
                        $providerPropertyId,
                        (int)$accId,
                        $providerRoomTypeId,
                        $roomTypeDefinitions
                    );
                    if ($roomTypeMap) {
                        $roomTypeMaps->put($providerRoomTypeId, $roomTypeMap);
                    }
                }

                $ratePlanMap = $ratePlanMaps->get($providerRatePlanId);
                if (!$ratePlanMap) {
                    $ratePlanMap = $this->ensureRatePlanMap(
                        $provider,
                        $adapter,
                        $providerPropertyId,
                        (int)$accId,
                        $providerRatePlanId,
                        $ratePlanDefinitions
                    );
                    if ($ratePlanMap) {
                        $ratePlanMaps->put($providerRatePlanId, $ratePlanMap);
                    }
                }

                if (!$roomTypeMap || !$ratePlanMap) {
                    $this->logger->error(__METHOD__, 'Failed to resolve provider mappings for availability rows', [

                        'provider_id' => $provider->id,
                        'provider_property_id' => $providerPropertyId,
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

    private function ensureRoomTypeMap(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerPropertyId,
        int $accommodationId,
        string $providerRoomTypeId,
        ?Collection &$definitions
    ): ?RoomTypeProviderMap {
        $map = RoomTypeProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('provider_room_type_id', $providerRoomTypeId)
            ->first();

        if ($map) {
            return $map;
        }

        $definitions ??= $this->fetchRoomTypeDefinitions($provider, $adapter, $providerPropertyId);
        $definition = $definitions->get($providerRoomTypeId) ?? [];

        return DB::transaction(function () use (
            $provider,
            $providerRoomTypeId,
            $accommodationId,
            $definition
        ) {
            $existing = RoomTypeProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('provider_room_type_id', $providerRoomTypeId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $roomType = $this->upsertRoomType($accommodationId, $definition, $providerRoomTypeId);

            return RoomTypeProviderMap::query()->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'provider_room_type_id' => $providerRoomTypeId,
                ],
                [
                    'room_type_id' => $roomType->id,
                    'fa_name' => $this->normalizeName($definition['fa_name'] ?? $roomType->fa_name, 'Room Type', $providerRoomTypeId),
                    'en_name' => $this->nullableString($definition['en_name'] ?? $roomType->en_name),
                ]
            );
        });
    }

    private function ensureRatePlanMap(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerPropertyId,
        int $accommodationId,
        string $providerRatePlanId,
        ?Collection &$definitions
    ): ?RatePlanProviderMap {
        $map = RatePlanProviderMap::query()
            ->where('provider_id', $provider->id)
            ->where('provider_rate_plan_id', $providerRatePlanId)
            ->first();

        if ($map) {
            return $map;
        }

        $definitions ??= $this->fetchRatePlanDefinitions($provider, $adapter, $providerPropertyId);
        $definition = $definitions->get($providerRatePlanId) ?? [];

        return DB::transaction(function () use (
            $provider,
            $providerRatePlanId,
            $accommodationId,
            $definition
        ) {
            $existing = RatePlanProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('provider_rate_plan_id', $providerRatePlanId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $ratePlan = $this->upsertRatePlan($accommodationId, $definition, $providerRatePlanId);

            return RatePlanProviderMap::query()->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'provider_rate_plan_id' => $providerRatePlanId,
                ],
                [
                    'rate_plan_id' => $ratePlan->id,
                    'fa_name' => $this->normalizeName($definition['fa_name'] ?? $ratePlan->fa_name, 'Rate Plan', $providerRatePlanId),
                    'en_name' => $this->nullableString($definition['en_name'] ?? $ratePlan->en_name),
                ]
            );
        });
    }

    private function fetchRoomTypeDefinitions(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerPropertyId
    ): Collection {
        try {
            return $adapter->fetchRoomTypes($providerPropertyId)
                ->filter(fn($item) => is_array($item))
                ->map(fn(array $item) => $item)
                ->filter(fn(array $item) => isset($item['room_type_id']))
                ->keyBy(fn(array $item) => (string)$item['room_type_id']);
        } catch (\Throwable $exception) {
            $context = [
                'provider_id' => $provider->id,
                'provider_property_id' => $providerPropertyId,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'exception' => $exception,
            ];

            $this->logProviderHttpError(__METHOD__, 'Failed to fetch provider room types', $context, $exception);

            return collect();
        }
    }

    private function fetchRatePlanDefinitions(
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $providerPropertyId
    ): Collection {
        try {
            return $adapter->fetchRatePlans($providerPropertyId)
                ->filter(fn($item) => is_array($item))
                ->map(fn(array $item) => $item)
                ->filter(fn(array $item) => isset($item['rate_plan_id']))
                ->keyBy(fn(array $item) => (string)$item['rate_plan_id']);
        } catch (\Throwable $exception) {
            $context = [
                'provider_id' => $provider->id,
                'provider_property_id' => $providerPropertyId,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'exception' => $exception,
            ];

            $this->logProviderHttpError(__METHOD__, 'Failed to fetch provider rate plans', $context, $exception);


            return collect();
        }
    }

    private function upsertRoomType(int $accommodationId, array $definition, string $providerRoomTypeId): RoomType
    {
        $faName = $this->normalizeName($definition['fa_name'] ?? null, 'Room Type', $providerRoomTypeId);

        $roomType = RoomType::query()->firstOrNew([
            'accommodation_id' => $accommodationId,
            'fa_name' => $faName,
        ]);

        $roomType->en_name = $this->nullableString($definition['en_name'] ?? $roomType->en_name);
        $roomType->capacity = $this->toNullableInt($definition['capacity'] ?? $roomType->capacity);
        $roomType->extra_capacity = $this->toNullableInt($definition['extra_capacity'] ?? $roomType->extra_capacity);
        $roomType->single_bed_count = $this->toNullableInt($definition['single_bed_count'] ?? $roomType->single_bed_count);
        $roomType->double_bed_count = $this->toNullableInt($definition['double_bed_count'] ?? $roomType->double_bed_count);
        $roomType->sofa_bed_count = $this->toNullableInt($definition['sofa_bed_count'] ?? $roomType->sofa_bed_count);
        $roomType->out_of_service = (bool)($definition['out_of_service'] ?? $roomType->out_of_service ?? false);
        $roomType->save();

        return $roomType;
    }

    private function upsertRatePlan(int $accommodationId, array $definition, string $providerRatePlanId): RatePlan
    {
        $faName = $this->normalizeName($definition['fa_name'] ?? null, 'Rate Plan', $providerRatePlanId);

        $ratePlan = RatePlan::query()->firstOrNew([
            'accommodation_id' => $accommodationId,
            'fa_name' => $faName,
        ]);

        $ratePlan->en_name = $this->nullableString($definition['en_name'] ?? $ratePlan->en_name);
        $ratePlan->meal_type = $this->sanitizeMealType($definition['meal_type'] ?? $ratePlan->meal_type);
        $ratePlan->food_board_type = $this->sanitizeFoodBoardType($definition['food_board_type'] ?? $ratePlan->food_board_type);
        $ratePlan->cancelable = (bool)($definition['cancelable'] ?? $ratePlan->cancelable ?? true);
        $ratePlan->sleeps = $this->toNullableInt($definition['sleeps'] ?? $ratePlan->sleeps);
        $ratePlan->min_stay = $this->toNullableInt($definition['min_stay'] ?? $ratePlan->min_stay);
        $ratePlan->max_stay = $this->toNullableInt($definition['max_stay'] ?? $ratePlan->max_stay);
        $ratePlan->facilities = $this->sanitizeFacilities($definition['facilities'] ?? $ratePlan->facilities);
        $ratePlan->save();

        return $ratePlan;
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


    private function logProviderHttpError(string $method, string $message, array $context, \Throwable $exception): void
    {
        $this->logger->error($method, $message, array_merge(

            $context,
            $this->buildHttpErrorContext($exception)
        ));
    }

    private function buildHttpErrorContext(\Throwable $exception): array
    {
        if (!$exception instanceof RequestException) {
            return [];
        }

        $response = $exception->response();
        $stats = $response?->transferStats();
        $request = $stats?->getRequest();

        return $this->filterContext([
            'request' => $this->formatRequestContext($request),
            'response' => $this->formatResponseContext($response),
        ]);
    }

    private function formatRequestContext(?RequestInterface $request): ?array

    {
        if (!$request) {
            return null;
        }

        $context = [
            'method' => $request->getMethod(),
            'url' => (string)$request->getUri(),
            'headers' => $this->normalizeHeaders($request->getHeaders()),
        ];

        $body = (string)$request->getBody();
        if ($body !== '') {

            $context['body'] = $this->truncateString($body);
        }

        return $this->filterContext($context);
    }

    private function formatResponseContext(?Response $response): ?array
    {
        if (!$response) {
            return null;
        }

        $context = [
            'status' => $response->status(),
            'headers' => $this->normalizeHeaders($response->headers()),
        ];

        $body = $response->body();
        if ($body !== '') {
            $context['body'] = $this->truncateString($body);
        }

        return $this->filterContext($context);
    }

    private function normalizeHeaders(?array $headers): ?array
    {
        if (!$headers) {
            return null;
        }

        foreach ($headers as $key => $value) {
            if (is_array($value) && count($value) === 1) {
                $headers[$key] = $value[0];
            }
        }

        return $headers;
    }

    private function filterContext(array $context): array
    {
        return array_filter($context, function ($value) {
            if ($value === null) {
                return false;
            }

            if (is_array($value)) {
                return !empty($value);
            }

            if (is_string($value)) {
                return $value !== '';
            }

            return true;
        });
    }

    private function truncateString(string $value, int $limit = 2000): string
    {
        return Str::limit($value, $limit, '...');
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

    private function normalizeName(?string $name, string $prefix, string $identifier): string
    {
        $normalized = trim((string)($name ?? ''));
        if ($normalized === '') {
            $normalized = sprintf('%s %s', $prefix, $identifier);
        }

        return Str::limit($normalized, 200, '');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return null;
        }

        return Str::limit($trimmed, 200, '');
    }

    private function sanitizeMealType(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : null;
        if ($value === null) {
            return null;
        }

        return in_array($value, self::ALLOWED_MEAL_TYPES, true) ? $value : null;
    }

    private function sanitizeFoodBoardType(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : null;
        if ($value === null) {
            return null;
        }

        return in_array($value, self::ALLOWED_FOOD_BOARD_TYPES, true) ? $value : null;
    }

    private function sanitizeFacilities(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
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
