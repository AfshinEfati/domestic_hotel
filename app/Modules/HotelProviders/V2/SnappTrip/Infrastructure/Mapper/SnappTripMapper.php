<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripMoney;
use Carbon\CarbonImmutable;

final class SnappTripMapper
{
    public function city(array $row): ?array
    {
        $id = $this->id($row['id'] ?? null);
        $faName = $this->string($row['title_fa'] ?? null);

        if ($id === null || $faName === null) {
            return null;
        }

        $state = is_array($row['state'] ?? null) ? $row['state'] : [];

        return [
            'id' => $id,
            'name' => $faName,
            'name_en' => $this->string($row['title_en'] ?? null),
            'provider_state_id' => $this->id($state['id'] ?? null),
            'province_name' => $this->string($state['title'] ?? null),
            'province_name_en' => null,
        ];
    }

    public function hotel(array $row): ?array
    {
        $id = $this->id($row['id'] ?? null);
        $faName = $this->string($row['title'] ?? null);

        if ($id === null || $faName === null) {
            return null;
        }

        $policies = is_array($row['policies'] ?? null) ? $row['policies'] : [];
        $location = is_array($row['location'] ?? null) ? $row['location'] : [];
        $city = is_array($row['city'] ?? null) ? $row['city'] : [];

        return [
            'provider_property_id' => $id,
            'provider_city_id' => $this->id($city['id'] ?? null),
            'fa_name' => $faName,
            'en_name' => $this->string($row['title_en'] ?? null),
            'accommodation_type' => $this->string($row['accommodation_type'] ?? null),
            'accommodation_title' => $this->string($row['accommodation_title'] ?? null),
            'star' => is_numeric($row['stars'] ?? null) ? (int) $row['stars'] : null,
            'address' => $this->string($row['address'] ?? null),
            'lat' => $this->float($location['lat'] ?? $location['latitude'] ?? null),
            'lng' => $this->float($location['lon'] ?? $location['lng'] ?? $location['longitude'] ?? null),
            'enabled' => ($row['enable'] ?? true) === true,
            'is_marketplace' => array_key_exists('is_marketplace', $row) ? (bool) $row['is_marketplace'] : null,
            'description' => $this->string($row['description'] ?? null),
            'policies' => [
                'max_infant_age' => $this->unsignedInt($policies['infant_age'] ?? null),
                'max_child_age' => $this->unsignedInt($policies['child_age'] ?? null),
                'check_in_time' => $this->string($policies['check_in_time'] ?? null),
                'check_out_time' => $this->string($policies['check_out_time'] ?? null),
                'cancellation_policy' => $this->string($policies['cancellation'] ?? null),
                'foreigners_fee' => array_key_exists('foreigners_fee', $policies) ? (bool) $policies['foreigners_fee'] : null,
                'free_transfer_policy' => $this->string($policies['free_transfer_policy'] ?? null),
                'free_transfers' => is_array($policies['free_transfers'] ?? null) ? $policies['free_transfers'] : null,
            ],
            'facilities' => collect(is_array($row['facilities'] ?? null) ? $row['facilities'] : [])
                ->map(fn ($facility) => is_array($facility) ? $this->facility($facility) : null)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public function room(array $row): ?array
    {
        $id = $this->id($row['id'] ?? null);
        $title = $this->string($row['title'] ?? null);

        if ($id === null || $title === null) {
            return null;
        }

        return [
            'provider_room_type_id' => $id,
            'provider_property_id' => $this->id($row['hotel_id'] ?? null),
            'fa_name' => $title,
            'board_type' => $this->string($row['board_type'] ?? null) ?? 'room_only',
            'adult_capacity' => $this->unsignedInt($row['adults'] ?? null),
            'extra_capacity' => $this->unsignedInt($row['extra_bed'] ?? null),
        ];
    }

    public function facility(array $row): ?array
    {
        $name = $this->string($row['title'] ?? null);

        return $name === null ? null : ['name' => $name];
    }

    /**
     * Flatten a SnappTrip hotel calendar into provider-neutral nightly rows.
     * All monetary fields cross the provider boundary here and are returned in IRR.
     * The returned price already belongs to the selected domestic/foreign offer;
     * extra_foreigner_price is never added a second time by the GDS.
     *
     * @return array{rows: array<int,array<string,mixed>>, packages: array<int,array<string,mixed>>}
     */
    public function hotelCalendar(array $payload, bool $foreigner): array
    {
        $rows = [];
        $packages = [];

        foreach (is_array($payload['racks'] ?? null) ? $payload['racks'] : [] as $rack) {
            if (!is_array($rack)) {
                continue;
            }
            foreach (is_array($rack['roomIDs'] ?? null) ? $rack['roomIDs'] : [] as $roomId) {
                $package = $this->package($roomId, $rack['checkin'] ?? null, $rack['checkout'] ?? null, null);
                if ($package !== null) {
                    $packages[] = $package;
                }
            }
        }

        foreach (is_array($payload['rooms'] ?? null) ? $payload['rooms'] : [] as $room) {
            if (!is_array($room)) {
                continue;
            }
            $roomId = $this->id($room['id'] ?? null);
            if ($roomId === null) {
                continue;
            }

            foreach (is_array($room['daily'] ?? null) ? $room['daily'] : [] as $day => $daily) {
                if (!is_array($daily)) {
                    continue;
                }
                $base = SnappTripMoney::toInternal($daily['price'] ?? null);
                $rack = SnappTripMoney::toInternal($daily['original_sell_price'] ?? null);
                $inventory = $this->unsignedInt($daily['availability'] ?? null);

                $rows[] = [
                    'provider_room_type_id' => $roomId,
                    'day' => (string) $day,
                    'foreigner' => $foreigner,
                    'inventory' => $inventory,
                    'rack_rate' => $rack,
                    'daily_rate' => $base,
                    'child_daily_rate' => SnappTripMoney::toInternal($daily['child_price'] ?? null),
                    'infant_daily_rate' => null,
                    'extend_bed_daily_rate' => SnappTripMoney::toInternal($daily['extra_bed_price'] ?? null),
                    'min_stay' => $this->positiveInt($daily['min_stay'] ?? null),
                    'max_stay' => null,
                    'cta' => false,
                    'ctd' => false,
                    'closed' => ($inventory ?? 0) <= 0 || $base === null,
                ];

                foreach (is_array($daily['racks'] ?? null) ? $daily['racks'] : [] as $dailyRack) {
                    if (!is_array($dailyRack)) {
                        continue;
                    }
                    $package = $this->package(
                        $roomId,
                        $dailyRack['checkin'] ?? null,
                        $dailyRack['checkout'] ?? null,
                        null,
                    );
                    if ($package !== null) {
                        $packages[] = $package;
                    }
                }
            }
        }

        return ['rows' => $rows, 'packages' => $packages];
    }

    /** @return array{rows: array<int,array<string,mixed>>, packages: array<int,array<string,mixed>>} */
    public function availability(array $payload, bool $foreigner): array
    {
        $rows = [];
        $packages = [];

        foreach (is_array($payload['availability'] ?? null) ? $payload['availability'] : [] as $item) {
            if (!is_array($item) || !is_array($item['room'] ?? null)) {
                continue;
            }
            $room = $this->room($item['room']);
            if ($room === null) {
                continue;
            }
            $pricing = is_array($item['pricing'] ?? null) ? $item['pricing'] : [];
            $base = SnappTripMoney::toInternal($pricing['price'] ?? null);
            $sell = SnappTripMoney::toInternal($pricing['original_sell_price'] ?? null);

            $rows[] = [
                'provider_room_type_id' => $room['provider_room_type_id'],
                'room' => $room,
                'from' => $this->string($item['from'] ?? null),
                'to' => $this->string($item['to'] ?? null),
                'foreigner' => $foreigner,
                'inventory' => $this->unsignedInt($item['availability'] ?? null),
                'price' => $base,
                'original_sell_price' => $sell,
                'child_price' => SnappTripMoney::toInternal($pricing['child_price'] ?? null),
                'extra_bed_price' => SnappTripMoney::toInternal($pricing['extra_bed_price'] ?? null),
                'min_stay' => $this->positiveInt($item['min_stay'] ?? null),
            ];

            $rackGroup = is_array($item['racks'] ?? null) ? $item['racks'] : [];
            $title = $this->string($rackGroup['title'] ?? null);
            foreach (is_array($rackGroup['racks'] ?? null) ? $rackGroup['racks'] : [] as $rack) {
                if (!is_array($rack)) {
                    continue;
                }
                $package = $this->package(
                    $room['provider_room_type_id'],
                    $rack['checkin'] ?? null,
                    $rack['checkout'] ?? null,
                    $title,
                );
                if ($package !== null) {
                    $packages[] = $package;
                }
            }
        }

        return ['rows' => $rows, 'packages' => $packages];
    }

    public function booking(array $payload): array
    {
        $payload['price'] = SnappTripMoney::toInternal($payload['price'] ?? null);
        $payload['original_sell_price'] = SnappTripMoney::toInternal($payload['original_sell_price'] ?? null);
        $payload['discount'] = SnappTripMoney::toInternal($payload['discount'] ?? null);

        return $payload;
    }

    public function cancellationInquiry(array $payload): array
    {
        foreach (['service_fee', 'user_penalty', 'user_penalty_total', 'user_refund_amount'] as $field) {
            $payload[$field] = SnappTripMoney::toInternal($payload[$field] ?? null);
        }

        return $payload;
    }

    public function balance(array $payload): ?int
    {
        return SnappTripMoney::toInternal($payload['balance'] ?? null);
    }

    private function package(mixed $roomId, mixed $checkIn, mixed $checkOut, ?string $title): ?array
    {
        $room = $this->id($roomId);
        $from = $this->date($checkIn);
        $to = $this->date($checkOut);

        if ($room === null || $from === null || $to === null || $from >= $to) {
            return null;
        }

        return [
            'provider_room_type_id' => $room,
            'title' => $title,
            'check_in' => $from,
            'check_out' => $to,
        ];
    }

    private function id(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
        if (is_numeric($value)) {
            return (string) ((int) $value);
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function unsignedInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function date(mixed $value): ?string
    {
        try {
            return $this->string($value) === null ? null : CarbonImmutable::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
