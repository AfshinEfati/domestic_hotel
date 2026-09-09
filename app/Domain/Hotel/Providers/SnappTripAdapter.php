<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\AccommodationProviderMap;
use App\Services\HotelDataSyncService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SnappTripAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    public function code(): string
    {
        return 'snap';
    }

    public function authenticate(): void
    {
        $token = $this->provider->config['token'] ?? null;
        if (!$token) {
            throw new RuntimeException('SnappTrip provider missing token (api-key)');
        }

        $this->setHeader('api-key', $token);
        $this->setHeader('Accept', 'application/json');
    }

    /**
     * @throws ConnectionException
     */
    public function fetchAvailability(
        string            $providerPropertyId,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): Collection
    {
        $this->authenticate();

        $providerId = (int)$this->provider->id;

        $map = AccommodationProviderMap::query()
            ->where('provider_id', $providerId)
            ->where('provider_property_id', $providerPropertyId)
            ->first();

        if (!$map) {
            Log::warning('SnappTrip: AccommodationProviderMap not found', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
            ]);
            return collect();
        }

        // 1) meta (short range)
        $roomMeta = $this->fetchRoomMetaFromAvailabilityEndpoint($providerPropertyId, $from);

        // 2) calendar (long range)
        try {
            $calendar = $this->client()->get(
                "/availability/hotels/{$providerPropertyId}/calendar",
                [
                    'from' => CarbonImmutable::parse($from)->format('Y-m-d'),
                    'to' => CarbonImmutable::parse($to)->format('Y-m-d'),
                ]
            )->json();
        } catch (ConnectionException $e) {
            Log::error('SnappTrip: calendar request connection failed', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
                'from' => CarbonImmutable::parse($from)->format('Y-m-d'),
                'to' => CarbonImmutable::parse($to)->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('SnappTrip: calendar request failed', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
                'from' => CarbonImmutable::parse($from)->format('Y-m-d'),
                'to' => CarbonImmutable::parse($to)->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            return collect();
        }

        $rooms = data_get($calendar, 'rooms', []);
        if (!is_array($rooms) || empty($rooms)) {
            Log::warning('SnappTrip: calendar returned empty rooms', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
                'from' => CarbonImmutable::parse($from)->format('Y-m-d'),
                'to' => CarbonImmutable::parse($to)->format('Y-m-d'),
            ]);
            return collect();
        }

        // 3) sync virtual room_types + rate_plans (NO extra snapp requests)
        $roomTypesData = $this->buildRoomTypesDataForSync($rooms, $roomMeta);

        if (empty($roomTypesData)) {
            Log::warning('SnappTrip: generated roomTypesData is empty (cannot sync room_types/rate_plans)', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
            ]);
        } else {
            try {
                /** @var HotelDataSyncService $syncService */
                $syncService = app(HotelDataSyncService::class);
                $syncService->syncRoomTypes($map, $roomTypesData);

                Log::info('SnappTrip: synced virtual room types & rate plans via HotelDataSyncService', [
                    'provider_id' => $providerId,
                    'property_id' => $providerPropertyId,
                    'room_types_count' => count($roomTypesData),
                ]);
            } catch (Throwable $e) {
                Log::error('SnappTrip: syncRoomTypes failed (room_types/rate_plans may be missing => calendars may not save)', [
                    'provider_id' => $providerId,
                    'property_id' => $providerPropertyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4) output normalized availability rows
        return collect($rooms)->flatMap(function (array $room) use ($roomMeta) {

            $roomId = $this->asNumericString($room['id'] ?? null);
            if ($roomId === null) {
                return collect();
            }

            $meta = $roomMeta->get($roomId, []);

            $roomTitle = (string)($meta['title'] ?? $room['name'] ?? $room['title'] ?? $roomId);
            $boardType = (string)($room['board_type'] ?? ($meta['board_type'] ?? 'default'));

            $ratePlanProviderId = $this->makeVirtualRatePlanProviderId($roomId, $boardType);
            $ratePlanName = $this->makeVirtualRatePlanName($roomTitle, $boardType);

            $daily = $room['daily'] ?? [];
            if (!is_array($daily) || empty($daily)) {
                return collect();
            }

            return collect($daily)->map(function (array $dayData, string $day) use (
                $roomId,
                $ratePlanProviderId,
                $ratePlanName
            ) {
                $inventory = (int)($dayData['availability'] ?? 0);

                return [
                    'day' => $day,
                    'inventory' => $inventory,
                    'rack_rate' => $this->toIntOrNull($dayData['original_sell_price'] ?? null),
                    'daily_rate' => $this->toIntOrNull($dayData['price'] ?? null),
                    'grs_rate' => null,
                    'min_stay' => $this->normalizeMinStay($dayData['min_stay'] ?? null),
                    'max_stay' => null,
                    'cta' => false,
                    'ctd' => false,
                    'closed' => $inventory <= 0,
                    'room_type_id' => $roomId,
                    'rate_plan_id' => $ratePlanProviderId,
                    'rate_plan_name' => $ratePlanName,
                ];
            });
        })->values();
    }

    /**
     * DISABLED FOR SNAPP (must not hit provider endpoints here)
     */
    public function fetchRoomTypes(string $providerPropertyId): ?Collection
    {
        return null;
    }

    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        return collect();
    }

    private function fetchRoomMetaFromAvailabilityEndpoint(
        string            $providerPropertyId,
        DateTimeInterface $from
    ): Collection
    {
        $providerId = (int)$this->provider->id;

        $checkin = CarbonImmutable::parse($from)->format('Y-m-d');
        $checkout = CarbonImmutable::parse($from)->addDays(2)->format('Y-m-d');

        try {
            $res = $this->client()->get('/availability/hotels', [
                'id' => (int)$providerPropertyId,
                'checkin' => $checkin,
                'checkout' => $checkout,
            ])->json();
        } catch (Throwable $e) {
            Log::warning('SnappTrip: availability(hotels) meta request failed', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
                'checkin' => $checkin,
                'checkout' => $checkout,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }

        $items = is_array($res) ? $res : [];
        $firstHotel = $items[0] ?? null;

        $avail = is_array($firstHotel) ? ($firstHotel['availability'] ?? []) : [];
        if (!is_array($avail) || empty($avail)) {
            Log::warning('SnappTrip: availability(hotels) meta returned empty', [
                'provider_id' => $providerId,
                'property_id' => $providerPropertyId,
                'checkin' => $checkin,
                'checkout' => $checkout,
            ]);
            return collect();
        }

        return collect($avail)
            ->map(fn($it) => is_array($it) ? ($it['room'] ?? null) : null)
            ->filter(fn($room) => is_array($room) && !empty($room['id']))
            ->mapWithKeys(function (array $room) {
                $id = $this->asNumericString($room['id'] ?? null);
                if ($id === null) {
                    return [];
                }

                return [$id => [
                    'id' => $id,
                    'title' => (string)($room['title'] ?? $room['name'] ?? $id),
                    'board_type' => (string)($room['board_type'] ?? 'default'),
                    'adults' => $room['adults'] ?? null,
                    'children' => $room['children'] ?? null,
                    'extra_bed' => $room['extra_bed'] ?? null,
                ]];
            });
    }

    private function buildRoomTypesDataForSync(array $calendarRooms, Collection $roomMeta): array
    {
        $out = [];

        foreach ($calendarRooms as $room) {
            if (!is_array($room)) continue;

            $roomId = $this->asNumericString($room['id'] ?? null);
            if ($roomId === null) continue;

            $meta = $roomMeta->get($roomId, []);

            $title = (string)($meta['title'] ?? $room['name'] ?? $room['title'] ?? $roomId);
            $boardType = (string)($room['board_type'] ?? ($meta['board_type'] ?? 'default'));

            $adults = $this->toIntOrNull($meta['adults'] ?? null);
            $children = $this->toIntOrNull($meta['children'] ?? null);
            $extra = $this->toIntOrNull($meta['extra_bed'] ?? null);

            $capacity = null;
            if (($adults ?? 0) + ($children ?? 0) > 0) {
                $capacity = (int)(($adults ?? 0) + ($children ?? 0));
            }

            $providerRatePlanId = $this->makeVirtualRatePlanProviderId($roomId, $boardType);
            $ratePlanName = $this->makeVirtualRatePlanName($title, $boardType);
            $mealTypeForDb = $this->mapMealTypeForDb($boardType);
            $out[] = [
                'room_type_id' => $roomId,
                'fa_name' => $title,
                'en_name' => null,
                'capacity' => $capacity,
                'extra' => ($extra !== null && $extra > 0) ? $extra : null,
                'rate_plans' => [
                    [
                        'id' => $providerRatePlanId,
                        'name' => $ratePlanName,
                        'name_en' => null,
                        'meal_type_included' => $mealTypeForDb,
                        'cancelable' => true,
                        'sleeps' => $capacity,
                        'min_stay' => null,
                        'max_stay' => null,
                    ],
                ],
            ];
        }

        $unique = [];
        foreach ($out as $row) {
            $unique[$row['room_type_id']] = $row;
        }

        return array_values($unique);
    }

    /**
     * Map Snapp board_type to YOUR DB allowed values for rate_plans.meal_type
     * If you don't know the enum set, safest is returning null.
     *
     * ✅ You can adjust these strings to whatever your DB expects, e.g:
     * - 'BB' / 'RO' / 'HB' / 'FB' / 'AI' ...
     * - or 'breakfast' / 'room_only' ...
     */
    private function mapMealTypeForDb(string $boardType): ?string
    {
        $bt = strtolower(trim($boardType));
        return match ($bt) {
            'bed_breakfast', 'bed_breakfasts', 'bb', 'breakfast' => 'breakfast',
            'room_only', 'ro' => null,
            'half_breakfast', 'half_board', 'hb' => 'half_board',
            'full_board' => 'full_board',
            default => null,
        };
    }

    private function makeVirtualRatePlanProviderId(string $roomId, string $boardType): string
    {
        $base = (int)$roomId;
        $code = $this->boardCode($boardType);
        return (string)(($base * 1000) + $code);
    }

    private function boardCode(string $boardType): int
    {
        $bt = strtolower(trim($boardType));

        return match ($bt) {
            'bed_breakfast', 'bed_breakfasts', 'bb', 'breakfast' => 1,
            'room_only', 'ro' => 2,
            'default', '', 'standard' => 9,
            default => (abs(crc32($bt)) % 900) + 100,
        };
    }

    private function makeVirtualRatePlanName(string $roomTitle, string $boardType): string
    {
        $bt = strtolower(trim($boardType));

        return match ($bt) {
            'bed_breakfast', 'bed_breakfasts', 'bb', 'breakfast' => "{$roomTitle} (BB)",
            'room_only', 'ro' => "{$roomTitle} (RO)",
            default => "{$roomTitle} (Standard)",
        };
    }

    private function normalizeMinStay(mixed $value): ?int
    {
        if (!is_numeric($value)) return null;
        $v = (int)$value;
        return $v > 0 ? $v : null;
    }

    private function toIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }

    private function asNumericString(mixed $value): ?string
    {
        if ($value === null) return null;
        if (is_int($value)) return (string)$value;
        if (is_string($value) && preg_match('/^\d+$/', $value)) return $value;
        if (is_numeric($value)) return (string)((int)$value);
        return null;
    }

    /* Unsupported */
    public function fetchCities(): Collection
    {
        return collect();
    }

    public function fetchProperties(): Collection
    {
        return collect();
    }

    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 1000): Collection
    {
        return collect();
    }

    public function reserve(array $payload): array
    {
        throw new RuntimeException('Not supported');
    }

    public function extendExpire(string $reserveId): array
    {
        throw new RuntimeException('Not supported');
    }

    public function book(array $payload): array
    {
        throw new RuntimeException('Not supported');
    }

    public function modify(array $payload): array
    {
        throw new RuntimeException('Not supported');
    }

    public function cancel(array $payload): array
    {
        throw new RuntimeException('Not supported');
    }

    public function reservesList(array $filters = []): Collection
    {
        return collect();
    }

    public function reserveDetails(string $reserveId): array
    {
        throw new RuntimeException('Not supported');
    }

    public function supportedWebhooks(): array
    {
        return [];
    }

    public function fetchFacilities(): Collection
    {
        return collect();
    }
}
