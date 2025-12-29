<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use Illuminate\Support\Collection;
use DateTimeInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

/**
 * SnappTrip Adapter
 * Auth: API Key in header
 */
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
            throw new \RuntimeException("SnappTrip provider missing token (api-key) in config");
        }
        $this->setHeader('api-key', $token);
    }

    /**
     * Fetch Cities
     * Endpoint: GET /cities/
     * @return Collection
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchCities(): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('/cities/')->throw()->json();
        return collect($res)->map(function ($c) {
            return [
                'id' => (string) ($c['id'] ?? ''),
                'name' => $c['title_fa'] ?? null,
                'name_en' => $c['title_en'] ?? null,
                'province_id' => (string) ($c['state']['id'] ?? ''),
                'province_name' => $c['state']['title'] ?? null,
                'country_id' => null, // Not provided
            ];
        });
    }

    /**
     * Fetch Properties (Hotels) by City
     * Endpoint: GET /cities/{id}/hotels
     * @param string $providerCityId
     * @param int $page
     * @param int $count
     * @return Collection
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection
    {
        $this->authenticate();

        $offset = ($page - 1) * $count;
        $res = $this->client()->get("/cities/{$providerCityId}/hotels", [
            'limit' => $count,
            'offset' => $offset,
        ])->throw()->json();

        return collect(data_get($res, 'items', []))->map(function ($h) use ($providerCityId) {
            return [
                'id' => (string) $h['id'],
                'city_provider_id' => (string) $providerCityId,
                'name' => $h['name'] ?? null,
                'fa_name' => $h['name'] ?? null,
                // Brief endpoint doesn't provide these, would need detail fetch
                'en_name' => null,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'star' => null,
                'type' => 'hotel',
            ];
        });
    }

    /**
     * Fetch Room Types
     * Endpoint: GET /hotels/rooms?id={id}
     * @param string $providerPropertyId
     * @return Collection
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRoomTypes(string $providerPropertyId): Collection
    {
        $this->authenticate();

        $res = $this->client()->get('/hotels/rooms', [
            'id' => $providerPropertyId,
        ])->throw()->json();

        // Response structure: items[0].rooms[]
        $hotelData = data_get($res, 'items.0', []);
        $rooms = data_get($hotelData, 'rooms', []);

        return collect($rooms)->map(function ($r) {
            return [
                'room_type_id' => (string) $r['id'],
                'fa_name' => $r['title'] ?? null,
                'en_name' => null,
                'capacity' => ($r['adults'] ?? 0) + ($r['children'] ?? 0),
                'adults' => $r['adults'] ?? 0,
                'children' => $r['children'] ?? 0,
                'extra' => $r['extra_bed'] ?? 0,
                'board_type' => $r['board_type'] ?? null, // Useful for rate plan mapping
            ];
        });
    }

    /**
     * Fetch Rate Plans
     * SnappTrip doesn't have explicit RatePlans endpoint.
     * We can infer them from Room Types or return empty if not applicable.
     * @param string $providerPropertyId
     * @return Collection
     */
    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        // Since rate plans are tied to rooms (board_type), we could return dummy plans
        // or rely on fetchAvailability to provide rate info.
        // For now, returning empty as per typical adapter pattern if not explicit.
        return collect([]);
    }

    /**
     * Fetch Availability
     * Endpoint: GET /availability/hotels/{id}/calendar
     * @param string $providerPropertyId
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return Collection
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchAvailability(
        string $providerPropertyId,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): Collection {
        $this->authenticate();

        $res = $this->client()->get("/availability/hotels/{$providerPropertyId}/calendar", [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ])->throw()->json();

        $hotelData = data_get($res, 'items.0', []);
        $rooms = data_get($hotelData, 'rooms', []);

        $availability = collect();

        foreach ($rooms as $room) {
            $roomId = (string) $room['id'];
            $dailyData = $room['daily'] ?? [];

            foreach ($dailyData as $dateStr => $data) {
                $availability->push([
                    'day' => $dateStr,
                    'room_type_id' => $roomId,
                    'inventory' => $data['availability'] ?? 0,
                    'daily_rate' => $data['price'] ?? 0,
                    'rack_rate' => $data['original_sell_price'] ?? 0,
                    'min_stay' => $data['min_stay'] ?? 1,
                    'max_stay' => null,
                    'cta' => false,
                    'ctd' => false,
                    'closed' => ($data['availability'] ?? 0) <= 0,
                    // Rate plan info is implicit in the room/price
                    'rate_plan_id' => null,
                ]);
            }
        }

        return $availability;
    }

    /**
     * Reserve
     * Endpoint: POST /booking/create
     * @param array $payload
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reserve(array $payload): array
    {
        $this->authenticate();

        // Map internal payload to SnappTrip payload
        // Expected internal payload structure:
        // [
        //   'hotel_id' => ...,
        //   'checkin' => 'Y-m-d',
        //   'checkout' => 'Y-m-d',
        //   'rooms' => [
        //      ['room_type_id' => ..., 'guests' => [['first_name' => ..., 'last_name' => ..., 'is_foreigner' => ...]]]
        //   ],
        //   'holder' => ['email' => ..., 'mobile' => ..., 'note' => ...]
        // ]

        $snappPayload = [
            'hotel_id' => (int) ($payload['hotel_id'] ?? 0),
            'checkin' => $payload['checkin'],
            'checkout' => $payload['checkout'],
            'email' => $payload['holder']['email'] ?? '',
            'phone' => $payload['holder']['mobile'] ?? '',
            'note' => $payload['holder']['note'] ?? '',
            'rooms' => [],
        ];

        foreach ($payload['rooms'] as $room) {
            $guests = [];
            foreach ($room['guests'] as $guest) {
                $guests[] = [
                    'first_name' => $guest['first_name'],
                    'last_name' => $guest['last_name'],
                    'foreigner' => $guest['is_foreigner'] ?? false,
                ];
            }

            $snappPayload['rooms'][] = [
                'room_id' => (int) $room['room_type_id'],
                'children' => $room['children_count'] ?? 0,
                'infants' => $room['infants_count'] ?? 0,
                'extra_beds' => $room['extra_beds'] ?? 0,
                'guests' => $guests,
            ];
        }

        $res = $this->client()->post('/booking/create', $snappPayload)->throw()->json();

        return [
            'reserve_id' => $res['reservation_code'],
            'expires_at' => now()->addMinutes(15)->toDateTimeString(), // Assumed 15 mins based on lock endpoint
            'price_summary' => $res['price'],
            'status' => $res['state'],
            'raw_response' => $res,
        ];
    }

    /**
     * Extend Expiration (Lock)
     * Endpoint: POST /booking/{code}/lock
     * @param string $reserveId
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     */
    public function extendExpire(string $reserveId): array
    {
        $this->authenticate();

        $this->client()->post("/booking/{$reserveId}/lock")->throw();

        return [
            'reserve_id' => $reserveId,
            'new_expires_at' => now()->addMinutes(15)->toDateTimeString(),
        ];
    }

    /**
     * Book (Confirm)
     * Endpoint: POST /booking/{code}/confirm
     * @param array $payload
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     */
    public function book(array $payload): array
    {
        $this->authenticate();

        $reserveId = $payload['reserve_id'];

        $res = $this->client()->post("/booking/{$reserveId}/confirm")->throw()->json();

        return [
            'reserve_id' => $res['reservation_code'] ?? $reserveId,
            'status' => $res['state'] ?? 'confirmed',
            'voucher_url' => null, // Not provided in response
            'reference_code' => $res['reservation_code'] ?? null,
        ];
    }

    /**
     * Modify
     * Not supported by API v2
     * @param array $payload
     * @return array
     */
    public function modify(array $payload): array
    {
        throw new \RuntimeException("Modify operation not supported by SnappTrip API.");
    }

    /**
     * Cancel
     * Not supported by API v2
     * @param array $payload
     * @return array
     */
    public function cancel(array $payload): array
    {
        throw new \RuntimeException("Cancel operation not supported by SnappTrip API.");
    }

    /**
     * Reserves List
     * Not supported by API v2
     * @param array $filters
     * @return Collection
     */
    public function reservesList(array $filters = []): Collection
    {
        return collect([]);
    }

    /**
     * Reserve Details
     * Endpoint: GET /booking/{code}
     * @param string $reserveId
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reserveDetails(string $reserveId): array
    {
        $this->authenticate();

        $res = $this->client()->get("/booking/{$reserveId}")->throw()->json();

        return [
            'reserve_id' => $res['reservation_code'],
            'status' => $res['state'],
            'total_price' => $res['price'],
            'created_at' => null, // Not provided
            'hotel_id' => (string) $res['hotel_id'],
            'rooms' => $res['rooms'] ?? [],
        ];
    }

    /**
     * Supported Webhooks
     * @return array
     */
    public function supportedWebhooks(): array
    {
        return [];
    }

    public function fetchFacilities()
    {
        $this->authenticate();
        // Endpoint: GET /hotels/facilities?id=...
        // Requires IDs, so leaving empty for generic call
        return collect([]);
    }

    public function fetchProperties()
    {
        // Not efficient to fetch all
        return collect([]);
    }
}
