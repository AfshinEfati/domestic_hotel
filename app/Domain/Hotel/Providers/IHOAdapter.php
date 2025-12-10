<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use DateTimeInterface;

/**
 * IranHotelOnline Adapter
 * Auth: no login, just base_url + version
 */
class IHOAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    protected string $apiPrefix;

    public function code(): string { return 'iho'; }

    public function authenticate(): void
    {
        $conf = $this->provider->config ?? [];
        $version = $conf['version'] ?? 1;
        $this->apiPrefix = "/api/app/v{$version}";
        // اگر بعداً توکن اضافه شد، اینجا ست میشه
    }

    /** -------------------- Basic Data --------------------
     * @throws ConnectionException|RequestException
     */

    public function fetchCities(): Collection
    {
        $this->authenticate();
        $res = $this->client()->get($this->apiPrefix.'/cities')->throw()->json();
        return collect(data_get($res, 'data', []))->map(fn($c) => [
            'id'      => (string)$c['id'],
            'fa_name' => $c['fa_name'],
            'en_name' => $c['en_name'],
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection
    {
        $this->authenticate();
        $res = $this->client()->get($this->apiPrefix.'/properties', [
            'city_id'  => $providerCityId,
            'page'     => $page,
            'per_page' => $count,
        ])->throw()->json();

        return collect(data_get($res, 'data', []))->map(fn($p) => [
            'property_id'      => (string)$p['id'],
            'fa_name'          => $p['fa_name'],
            'en_name'          => $p['en_name'] ?? null,
            'city_provider_id' => $providerCityId,
            'lat'              => data_get($p, 'lat'),
            'lng'              => data_get($p, 'lng'),
            'address'          => data_get($p, 'address'),
            'star'             => data_get($p, 'star'),
            'type'             => data_get($p, 'type'),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRoomTypes(string $providerPropertyId): Collection
    {
        $this->authenticate();
        $res = $this->client()->get($this->apiPrefix.'/room-types', [
            'property_id' => $providerPropertyId,
        ])->throw()->json();

        return collect(data_get($res, 'data', []))->map(fn($rt) => [
            'room_type_id' => (string)$rt['id'],
            'fa_name'      => $rt['fa_name'],
            'en_name'      => $rt['en_name'] ?? null,
            'capacity'     => data_get($rt, 'capacity'),
            'extra'        => data_get($rt, 'extra_capacity'),
        ]);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        $this->authenticate();
        $res = $this->client()->get($this->apiPrefix.'/rate-plans', [
            'property_id' => $providerPropertyId,
        ])->throw()->json();

        return collect(data_get($res, 'data', []))->map(fn($rp) => [
            'rate_plan_id' => (string)$rp['id'],
            'fa_name'      => $rp['fa_name'],
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
        $res = $this->client()->get($this->apiPrefix.'/availability', [
            'property_id' => $providerPropertyId,
            'from'        => $from->format('Y-m-d'),
            'to'          => $to->format('Y-m-d'),
        ])->throw()->json();

        $rooms = data_get($res, 'data.rooms', []);
        return collect($rooms)->flatMap(function ($room) {
            $roomTypeId = (string)data_get($room, 'room_type_id', '');
            $ratePlans  = data_get($room, 'rate_plans', []);
            return collect($ratePlans)->flatMap(function ($rp) use ($roomTypeId) {
                $rpId   = (string)data_get($rp, 'id', '');
                $rpName = data_get($rp, 'fa_name', data_get($rp, 'name', ''));
                $prices = data_get($rp, 'prices', []);
                return collect($prices)->map(fn($price) => [
                    'day'            => data_get($price, 'day'),
                    'inventory'      => data_get($price, 'inventory'),
                    'rack_rate'      => data_get($price, 'rack_rate'),
                    'daily_rate'     => data_get($price, 'daily_rate'),
                    'grs_rate'       => data_get($price, 'net_rate'),
                    'min_stay'       => data_get($price, 'min_stay'),
                    'max_stay'       => data_get($price, 'max_stay'),
                    'cta'            => (bool)data_get($price, 'cta', false),
                    'ctd'            => (bool)data_get($price, 'ctd', false),
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
     */

    public function reserve(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post($this->apiPrefix.'/reserve', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id'),
            'expires_at'   => data_get($val, 'expires_at'),
            'price_summary'=> data_get($val, 'price'),
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
        $res = $this->client()->post($this->apiPrefix.'/reserve/extend', [
            'reserve_id' => $reserveId,
        ])->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'            => true,
            'reserve_id'    => (string)($val['reserve_id'] ?? $reserveId),
            'new_expires_at'=> data_get($val, 'expires_at'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function book(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post($this->apiPrefix.'/book', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'             => true,
            'reference_code' => (string)data_get($val, 'reference_code'),
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
        $res = $this->client()->post($this->apiPrefix.'/modify', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'              => true,
            'reserve_id'      => (string)data_get($val, 'reserve_id'),
            'status'          => data_get($val, 'status'),
            'difference_price'=> data_get($val, 'difference_price'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function cancel(array $payload): array
    {
        $this->authenticate();
        $res = $this->client()->post($this->apiPrefix.'/cancel', $payload)->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id'),
            'status'       => data_get($val, 'status'),
            'refund_amount'=> data_get($val, 'refund_amount'),
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function reservesList(array $filters = []): Collection
    {
        $this->authenticate();
        $res = $this->client()->get($this->apiPrefix.'/reserves', $filters)->throw()->json();
        $items = data_get($res, 'data.items', []);
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
        $res = $this->client()->get($this->apiPrefix."/reserves/{$reserveId}")->throw()->json();
        $val = data_get($res, 'data', []);
        return [
            'ok'           => true,
            'reserve_id'   => (string)data_get($val, 'reserve_id', $reserveId),
            'status'       => data_get($val, 'status'),
            'property_id'  => (string)data_get($val, 'property_id'),
            'room_type_id' => (string)data_get($val, 'room_type_id'),
            'rate_plan_id' => (string)data_get($val, 'rate_plan_id'),
            'guests'       => data_get($val, 'guests', []),
            'price_summary'=> data_get($val, 'price'),
            'check_in'     => data_get($val, 'check_in'),
            'check_out'    => data_get($val, 'check_out'),
            'voucher_url'  => data_get($val, 'voucher_url'),
        ];
    }

    /** -------------------- Webhooks -------------------- */
    public function supportedWebhooks(): array
    {
        return ['AvailableChanged','ReserveChanged'];
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
