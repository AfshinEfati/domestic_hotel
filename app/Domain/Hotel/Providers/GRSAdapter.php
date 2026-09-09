<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use DateTimeInterface;

/**
 * GRS (Aghamat24) Adapter
 * Auth: static token from provider->config['token']
 */
class GRSAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    public function code(): string
    {
        return 'grs';
    }

    public function authenticate(): void
    {
        $token = $this->provider->config['token'] ?? null;
        if (!$token) {
            throw new \RuntimeException("GRS provider missing token in config");
        }
        $this->setHeader('Client-Token', $token);
    }

    /** -------------------- Basic Data --------------------
     * @throws RequestException
     * @throws ConnectionException
     */

    public function fetchCities(): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('/v1/cities')->throw()->json();

        return collect(data_get($res, 'value.cities', []))->map(function ($c) {
            return [
                'id'                    => (string)($c['id'] ?? ''),
                'name'                  => $c['name'] ?? null,
                'name_ar'               => $c['name_ar'] ?? null,
                'name_en'               => $c['name_en'] ?? null,

                'province_id'           => $c['province_id'] ?? null,
                'province_name'         => $c['province_name'] ?? null,
                'province_name_ar'      => $c['province_name_ar'] ?? null,
                'province_name_en'      => $c['province_name_en'] ?? null,

                'country_id'            => $c['country_id'] ?? null,
                'country_name'          => $c['country_name'] ?? null,
                'country_name_ar'       => $c['country_name_ar'] ?? null,
                'country_name_en'       => $c['country_name_en'] ?? null,
                'country_code_alpha_2'  => $c['country_code_alpha_2'] ?? null,
                'country_code_alpha_3'  => $c['country_code_alpha_3'] ?? null,
            ];
        });
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection
    {
        $this->authenticate();

        $filters = [
            ['name' => 'city_id', 'operand' => 'IsEqualTo', 'value' => (int)$providerCityId],
        ];

        $res = $this->client()->get('/v1/properties', [
            'filters' => $filters,
            'page'    => $page,
            'count'   => $count,
        ])->throw()->json();
        $properties = data_get($res, 'value.properties', []);
        return collect($properties)->map(function ($p) use ($providerCityId) {
            return [
                'id'         => (string)$p['id'],
                'city_id'    => (string)$providerCityId,
                'name'       => $p['name'] ?? null,
                'name_en'    => $p['name_en'] ?? null,
                'type'       => $p['type'] ?? 'hotel',
                'type_en'    => $p['type_en'] ?? 'Hotel',
                'star'       => $p['star'] ?? null,
                'grade'      => $p['grade'] ?? null,
                'address'    => $p['address'] ?? null,
                'address_en' => $p['address_en'] ?? null,
                'latitude'   => $p['latitude'] ?? null,
                'longitude'  => $p['longitude'] ?? null,
                'facilities' => $p['facilities'] ?? [],
            ];
        });
    }


    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchProperties(): array
    {
        $this->authenticate();
        $res = $this->client()
            ->get('/v1/properties', [
                'page'  => 1,
                'count' => 5000,
            ])
            ->throw()
            ->json();

        return data_get($res, 'value.properties', []);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRoomTypes(string $providerPropertyId): Collection
    {
        $this->authenticate();
        $res = $this->client()
            ->get("/v1/properties/{$providerPropertyId}")
            ->throw()
            ->json();

        $property = data_get($res, 'value.property', []);

        $roomTypes = data_get($property, 'room_types', data_get($res, 'value.room_types', []));
        if (!is_array($roomTypes)) {
            $roomTypes = [];
        }

        $facilities = data_get($property, 'facilities', []);
        if (!is_array($facilities)) {
            $facilities = [];
        }

        $rules = data_get($property, 'rules', []);
        if (!is_array($rules)) {
            $rules = [];
        }

        return collect($roomTypes)->map(fn($r) => [
            'room_type_id' => (string)$r['id'],
            'fa_name'      => $r['name'],
            'en_name'      => $r['name_en'] ?? null,
            'capacity'     => data_get($r, 'capacity'),
            'extra'        => data_get($r, 'extra_capacity'),
            'rate_plans'   => data_get($r, 'rate_plans', []),
            'property_facilities' => $facilities,
            'property_rules' => $rules,
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('/v1/rate-plans', [
            'property_id' => $providerPropertyId,
        ])->throw()->json();

        return collect(data_get($res, 'value.rate_plans', []))->map(fn($rp) => [
            'rate_plan_id' => (string)$rp['id'],
            'fa_name'      => $rp['name'],
            'en_name'      => $rp['en_name'] ?? null,
            'meal_type'    => data_get($rp, 'meal_type'),
            'sleeps'       => data_get($rp, 'sleeps'),
            'cancelable'   => (bool)data_get($rp, 'cancelable', true),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchAvailability(string $providerPropertyId, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('/v1/available-rooms', [
            'property_id' => $providerPropertyId,
            'check_in'    => $from->format('Y-m-d'),
            'check_out'   => $to->format('Y-m-d'),
        ])->throw()->json();
        $rooms = data_get($res, 'value.rooms', []);
        return collect($rooms)->flatMap(function ($room) {
            $roomTypeId = (string)data_get($room, 'room_type_id', '');
            $ratePlans  = data_get($room, 'rate_plans', []);
            return collect($ratePlans)->flatMap(function ($rp) use ($roomTypeId) {
                $rpId   = (string)data_get($rp, 'id', '');
                $rpName = data_get($rp, 'name', '');
                $prices = data_get($rp, 'prices', []);
                return collect($prices)->map(fn($price) => [
                    'day'            => data_get($price, 'day'),
                    'inventory'      => data_get($price, 'inventory'),
                    'rack_rate'      => data_get($price, 'rack_rate'),
                    'daily_rate'     => data_get($price, 'daily_rate'),
                    'grs_rate'       => data_get($price, 'grs_rate'),
                    'min_stay'       => data_get($price, 'min_stay'),
                    'max_stay'       => data_get($price, 'max_stay'),
                    'cta'            => (bool)data_get($price, 'close_to_arrival', false),
                    'ctd'            => (bool)data_get($price, 'close_to_departure', false),
                    'closed'         => (bool)data_get($price, 'closed', false),
                    'room_type_id'   => $roomTypeId,
                    'rate_plan_id'   => $rpId,
                    'rate_plan_name' => $rpName,
                ]);
            });
        });
    }

    /** -------------------- Reservation Flow --------------------
     * @throws ConnectionException
     * @throws RequestException
     */

    public function reserve(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('/v1/reserve', $payload)->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id'),
            'expires_at'   => data_get($val, 'expires_at'),
            'price_summary' => data_get($val, 'price_summary'),
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
        $res = $this->client()->post('/v1/reserve/extend', [
            'reserve_id' => $reserveId,
        ])->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'            => true,
            'reserve_id'    => (string)($val['reserve_id'] ?? $reserveId),
            'new_expires_at' => data_get($val, 'expires_at'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function book(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('/v1/book', $payload)->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'             => true,
            'reference_code' => (string)data_get($val, 'reference_code'),
            'pnr'            => data_get($val, 'pnr'),
            'status'         => data_get($val, 'status'),
            'voucher_url'    => data_get($val, 'voucher_url'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function modify(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('/v1/modify', $payload)->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'              => true,
            'reserve_id'      => (string)data_get($val, 'reserve_id'),
            'status'          => data_get($val, 'status'),
            'difference_price' => data_get($val, 'difference_price'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function cancel(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post('/v1/cancel', $payload)->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id'),
            'status'       => data_get($val, 'status'),
            'refund_amount' => data_get($val, 'refund_amount'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reservesList(array $filters = []): Collection
    {
        $this->authenticate();
        $res = $this->client()->get('/v1/reserves', $filters)->throw()->json();
        $items = data_get($res, 'value.items', []);
        return collect($items)->map(fn($it) => [
            'reserve_id'  => (string)data_get($it, 'reserve_id'),
            'status'      => data_get($it, 'status'),
            'created_at'  => data_get($it, 'created_at'),
            'property_id' => (string)data_get($it, 'property_id'),
            'guest_name'  => data_get($it, 'guest_name'),
            'total_price' => data_get($it, 'total_price'),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reserveDetails(string $reserveId): array
    {
        $this->authenticate();
        $res = $this->client()->get("/v1/reserves/{$reserveId}")->throw()->json();
        $val = data_get($res, 'value', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id', $reserveId),
            'status'       => data_get($val, 'status'),
            'property_id'  => (string)data_get($val, 'property_id'),
            'room_type_id' => (string)data_get($val, 'room_type_id'),
            'rate_plan_id' => (string)data_get($val, 'rate_plan_id'),
            'guests'       => data_get($val, 'guests', []),
            'price_summary' => data_get($val, 'price_summary'),
            'check_in'     => data_get($val, 'check_in'),
            'check_out'    => data_get($val, 'check_out'),
            'voucher_url'  => data_get($val, 'voucher_url'),
        ];
    }

    /** -------------------- Webhooks -------------------- */
    public function supportedWebhooks(): array
    {
        return ['AvailableChanged', 'ReserveChanged', 'PropertyChanged', 'ReserveActivity'];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchFacilities(): array
    {
        $this->authenticate();
        $res = $this->client()->get('/v1/facilities')->throw()->json();

        return data_get($res, 'value.facilities', []);
    }
}
