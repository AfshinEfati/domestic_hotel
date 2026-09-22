<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\City;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use RuntimeException;

/**
 * Parto CRS Adapter
 * Auth: username (access_key) + hashed password (sha512 of secret_key) -> token
 */
class PartoAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    public function code(): string
    {
        return 'parto';
    }

    /**
     * Authenticate with Parto API
     * - If token expired or missing, login and get a new one.
     * - If token is valid, keep it but extend expire_at by 20 minutes after each request.
     * @throws ConnectionException
     */
    public function authenticate(): void
    {
        $conf = $this->provider->config ?? [];
        $token = $conf['auth_token'] ?? null;
        $expire = isset($conf['expire_at']) ? Carbon::parse($conf['expire_at']) : null;
        if (!$token || !$expire || $expire->isPast()) {
            $hashedPassword = strtoupper(hash('sha512', $conf['secret_key'] ?? ''));
            $authUrl = $this->baseUrl . '/Authenticate/CreateSession';
            $response = $this->client()->withHeaders([
                'Accept' => 'application/json',
            ])->post($authUrl, [
                'OfficeId' => $conf['access_key'] ?? '',
                "UserName"=> "Api",
                'Password' => $hashedPassword,
            ]);
            $response = $response->json();
            $token = data_get($response, 'SessionId');
            if (!$token) {
                throw new RuntimeException("Failed to authenticate with Parto");
            }

            $expireAt = now()->addMinutes(20);

            // Update provider config in DB
            $this->provider->update([
                'config' => array_merge($conf, [
                    'auth_token' => $token,
                    'expire_at' => $expireAt->toDateTimeString(),
                ]),
            ]);
        }

        // Always extend expiry after any request
        $this->provider->update([
            'config' => array_merge($conf, [
                'auth_token' => $token,
                'expire_at' => now()->addMinutes(20)->toDateTimeString(),
            ]),
        ]);

        $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    /** -------------------- Basic Data --------------------
     * @throws ConnectionException
     * @throws RequestException
     */

    public function fetchCities(): Collection
    {
        $filePath = database_path('seeders/data/DomesticPropertyCity.json');

        if (!file_exists($filePath)) {
            return collect();
        }

        $cities = json_decode(file_get_contents($filePath), true);

        if (!is_array($cities)) {
            return collect();
        }

        return collect($cities)->map(fn (array $city) => [
            'id'                   => (string) ($city['Id'] ?? ''),
            'name'                 => $city['NameFa'] ?? null,
            'name_ar'              => null,
            'name_en'              => $city['Name'] ?? null,

            'province_id'          => null,
            'province_name'        => $city['province_name'] ?? null,
            'province_name_ar'     => null,
            'province_name_en'     => $city['province_name_en'] ?? null,

            'country_id'           => null,
            'country_name'         => $city['country_name'] ?? null,
            'country_name_ar'      => null,
            'country_name_en'      => $city['country_name_en'] ?? null,
            'country_code_alpha_2' => $city['country_code_alpha_2'] ?? 'IR',
            'country_code_alpha_3' => $city['country_code_alpha_3'] ?? 'IRN',
        ]);
    }

    /**
     */
    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection
    {
        $file = database_path('seeders/data/DomesticProperty_0-25000.json');
        if (!file_exists($file)) {
            return collect();
        }
        $data = json_decode(file_get_contents($file), true);
        $properties = collect($data)
            ->filter(fn($p) => (int)$p['PropertyCityId'] === (int)$providerCityId);
        return $properties->map(function ($p) use ($providerCityId) {
            return [
                'id' => (string)$p['Id'],
                'city_id' => (string)$providerCityId,
                'name' => $p['NameFa'] ?? null,
                'name_en' => $p['Name'] ?? null,
                'type' => 'hotel',
                'type_en' => 'Hotel',
                'star' => (int)($p['Rating'] ?? 0),
                'grade' => null,
                'address' => $p['AddressFa'] ?? null,
                'address_en' => $p['Address'] ?? null,
                'latitude' => $this->normalizeLatLng($p['Latitude'] ?? null),
                'longitude' => $this->normalizeLatLng($p['Longitude'] ?? null),
                'facilities' => $p['DomesticPropertyFacility'] ?? [],
            ];
        });
    }

    protected function normalizeLatLng($value): ?float
    {
        if (!$value) {
            return null;
        }
        $val = (float)$value;
        return (abs($val) > 0 && abs($val) <= 180) ? $val : null;
    }


    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRoomTypes(string $providerPropertyId): ?Collection
    {
        return null;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('hotel/rate-plans', [
            'propertyId' => $providerPropertyId,
        ])->throw()->json();

        return collect(data_get($res, 'data', []))->map(fn($rp) => [
            'rate_plan_id' => (string)$rp['id'],
            'fa_name' => $rp['fa_name'],
            'en_name' => $rp['en_name'] ?? null,
            'meal_type' => data_get($rp, 'meal_type'),
            'sleeps' => data_get($rp, 'sleeps'),
            'cancelable' => (bool)data_get($rp, 'cancelable', true),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchAvailability(string $providerPropertyId, \DateTimeInterface $from, \DateTimeInterface $to): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('hotel/availability', [
            'propertyId' => $providerPropertyId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ])->throw()->json();

        $rooms = data_get($res, 'data.rooms', []);
        return collect($rooms)->flatMap(function ($room) {
            $roomTypeId = (string)data_get($room, 'roomTypeId', '');
            $ratePlans = data_get($room, 'ratePlans', []);
            return collect($ratePlans)->flatMap(function ($rp) use ($roomTypeId) {
                $rpId = (string)data_get($rp, 'ratePlanId', '');
                $rpName = data_get($rp, 'name', '');
                $prices = data_get($rp, 'prices', []);
                return collect($prices)->map(fn($price) => [
                    'day' => data_get($price, 'date'),
                    'inventory' => data_get($price, 'inventory'),
                    'rack_rate' => data_get($price, 'rackRate'),
                    'daily_rate' => data_get($price, 'dailyRate'),
                    'grs_rate' => data_get($price, 'net'),
                    'min_stay' => data_get($price, 'minStay'),
                    'max_stay' => data_get($price, 'maxStay'),
                    'cta' => (bool)data_get($price, 'cta', false),
                    'ctd' => (bool)data_get($price, 'ctd', false),
                    'closed' => (bool)data_get($price, 'closed', false),
                    'room_type_id' => $roomTypeId,
                    'rate_plan_id' => $rpId,
                    'rate_plan_name' => $rpName,
                ]);
            });
        });
    }

    /** -------------------- Reservation Flow --------------------
     * @throws ConnectionException|RequestException
     */

    public function reserve(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('hotel/reserve', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reserve_id' => (string)data_get($val, 'reserveId'),
            'expires_at' => data_get($val, 'expiresAt'),
            'price_summary' => data_get($val, 'price'),
            'hold_details' => data_get($val, 'hold'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function extendExpire(string $reserveId): array
    {
        $this->authenticate();
        $res = $this->client()->post('hotel/reserve/extend', [
            'reserveId' => $reserveId,
        ])->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reserve_id' => (string)($val['reserveId'] ?? $reserveId),
            'new_expires_at' => data_get($val, 'expiresAt'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function book(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('hotel/book', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reference_code' => (string)data_get($val, 'referenceCode'),
            'status' => data_get($val, 'status'),
            'voucher_url' => data_get($val, 'voucherUrl'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function modify(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('hotel/modify', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reserve_id' => (string)data_get($val, 'reserveId'),
            'status' => data_get($val, 'status'),
            'difference_price' => data_get($val, 'differencePrice'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function cancel(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('hotel/cancel', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reserve_id' => (string)data_get($val, 'reserveId'),
            'status' => data_get($val, 'status'),
            'refund_amount' => data_get($val, 'refundAmount'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reservesList(array $filters = []): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('hotel/reserves', $filters)->throw()->json();
        $items = data_get($res, 'data.items', []);
        return collect($items)->map(fn($it) => [
            'reserve_id' => (string)data_get($it, 'reserveId'),
            'status' => data_get($it, 'status'),
            'created_at' => data_get($it, 'createdAt'),
            'property_id' => (string)data_get($it, 'propertyId'),
            'guest_name' => data_get($it, 'guestName'),
            'total_price' => data_get($it, 'totalPrice'),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reserveDetails(string $reserveId): array
    {
        $this->authenticate();
        $res = $this->client()->get("hotel/reserves/{$reserveId}")->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok' => true,
            'reserve_id' => (string)data_get($val, 'reserveId', $reserveId),
            'status' => data_get($val, 'status'),
            'property_id' => (string)data_get($val, 'propertyId'),
            'room_type_id' => (string)data_get($val, 'roomTypeId'),
            'rate_plan_id' => (string)data_get($val, 'ratePlanId'),
            'guests' => data_get($val, 'guests', []),
            'price_summary' => data_get($val, 'price'),
            'check_in' => data_get($val, 'checkIn'),
            'check_out' => data_get($val, 'checkOut'),
            'voucher_url' => data_get($val, 'voucherUrl'),
        ];
    }

    /** -------------------- Webhooks -------------------- */
    public function supportedWebhooks(): array
    {
        return ['AvailableChanged', 'ReserveChanged', 'PropertyChanged'];
    }

    public function fetchFacilities(): Collection
    {
        return collect();
    }

    public function fetchProperties(): Collection
    {
        return collect();
    }
}
