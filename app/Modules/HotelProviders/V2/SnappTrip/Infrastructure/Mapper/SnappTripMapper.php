<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripMoney;
use Carbon\CarbonImmutable;

final class SnappTripMapper
{
    public function city(array $row): ?array
    {
        $id = $this->id($row['id'] ?? null);
        $name = $this->string($row['title_fa'] ?? null);
        if ($id === null || $name === null) {
            return null;
        }

        return [
            'id' => $id,
            'name' => $name,
            'name_en' => $this->string($row['title_en'] ?? null),
            'provider_state_id' => $this->id(data_get($row, 'state.id')),
            'province_name' => $this->string(data_get($row, 'state.title')),
        ];
    }

    public function hotel(array $row): ?array
    {
        $id = $this->id($row['id'] ?? null);
        $name = $this->string($row['title_fa'] ?? $row['name'] ?? null);
        if ($id === null || $name === null) {
            return null;
        }

        return [
            'id' => $id,
            'name' => $name,
            'name_en' => $this->string($row['title_en'] ?? $row['name_en'] ?? null),
            'provider_city_id' => $this->id($row['city_id'] ?? data_get($row, 'city.id')),
            'star' => $this->integer($row['star'] ?? $row['stars'] ?? null),
            'address' => $this->string($row['address'] ?? null),
            'latitude' => $this->coordinate($row['latitude'] ?? $row['lat'] ?? null, 90),
            'longitude' => $this->coordinate($row['longitude'] ?? $row['lng'] ?? null, 180),
        ];
    }

    public function hotelDetails(array $payload): array
    {
        $hotel = is_array($payload['hotel'] ?? null) ? $payload['hotel'] : $payload;
        $mappedHotel = $this->hotel($hotel) ?? [];

        return [
            'hotel' => $mappedHotel,
            'facilities' => $this->facilities($hotel['facilities'] ?? $payload['facilities'] ?? []),
            'rooms' => $this->rooms($hotel['rooms'] ?? $payload['rooms'] ?? []),
            'rules' => is_array($hotel['rules'] ?? $payload['rules'] ?? null)
                ? ($hotel['rules'] ?? $payload['rules'])
                : [],
            'child_policy' => is_array($hotel['child_policy'] ?? $payload['child_policy'] ?? null)
                ? ($hotel['child_policy'] ?? $payload['child_policy'])
                : [],
        ];
    }

    public function hotelCalendar(array $payload, bool $foreigner): array
    {
        $rows = [];
        $packages = [];
        $rooms = $payload['rooms'] ?? $payload['data'] ?? [];
        if (!is_array($rooms)) {
            return ['rows' => [], 'packages' => []];
        }

        foreach ($rooms as $room) {
            if (!is_array($room)) {
                continue;
            }

            $providerRoomId = $this->id($room['id'] ?? $room['room_id'] ?? null);
            if ($providerRoomId === null) {
                continue;
            }

            $daily = $room['daily'] ?? $room['calendar'] ?? [];
            if (is_array($daily)) {
                foreach ($daily as $day => $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $date = $this->date(is_string($day) ? $day : ($item['date'] ?? null));
                    if ($date === null) {
                        continue;
                    }

                    $base = SnappTripMoney::toInternal($item['price'] ?? null);
                    $rack = SnappTripMoney::toInternal($item['original_sell_price'] ?? null);
                    if ($base === null) {
                        continue;
                    }

                    $rows[] = [
                        'provider_room_type_id' => $providerRoomId,
                        'day' => $date,
                        'rack_rate' => $rack ?? $base,
                        'daily_rate' => $base,
                        'child_daily_rate' => SnappTripMoney::toInternal($item['child_price'] ?? null),
                        'infant_daily_rate' => SnappTripMoney::toInternal($item['infant_price'] ?? null),
                        'extend_bed_daily_rate' => SnappTripMoney::toInternal($item['extra_bed_price'] ?? null),
                        'inventory' => max(0, $this->integer($item['availability'] ?? $item['inventory'] ?? 0) ?? 0),
                        'min_stay' => max(1, $this->integer($item['min_stay'] ?? 1) ?? 1),
                        'closed' => (bool) ($item['closed'] ?? false),
                        'foreigner' => $foreigner,
                    ];
                }
            }

            $racks = $room['racks'] ?? $room['packages'] ?? [];
            if (!is_array($racks)) {
                continue;
            }

            foreach ($racks as $rack) {
                if (!is_array($rack)) {
                    continue;
                }

                $title = $this->string($rack['title'] ?? $rack['name'] ?? null);
                $package = $this->package(
                    $providerRoomId,
                    $rack['checkin'] ?? $rack['check_in'] ?? null,
                    $rack['checkout'] ?? $rack['check_out'] ?? null,
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
        return SnappTripMoney::toInternalSigned($payload['balance'] ?? null);
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

    /** @return array<int,array<string,mixed>> */
    private function facilities(mixed $facilities): array
    {
        if (!is_array($facilities)) {
            return [];
        }

        $result = [];
        foreach ($facilities as $facility) {
            if (!is_array($facility)) {
                continue;
            }

            $name = $this->string($facility['title'] ?? $facility['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $result[] = [
                'id' => $this->id($facility['id'] ?? null),
                'name' => $name,
                'group_name' => $this->string($facility['category'] ?? $facility['group_name'] ?? null),
            ];
        }

        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    private function rooms(mixed $rooms): array
    {
        if (!is_array($rooms)) {
            return [];
        }

        $result = [];
        foreach ($rooms as $room) {
            if (!is_array($room)) {
                continue;
            }

            $id = $this->id($room['id'] ?? $room['room_id'] ?? null);
            $name = $this->string($room['title'] ?? $room['name'] ?? null);
            if ($id === null || $name === null) {
                continue;
            }

            $result[] = [
                'id' => $id,
                'name' => $name,
                'capacity' => max(0, $this->integer($room['capacity'] ?? $room['adult_capacity'] ?? 0) ?? 0),
                'extra_capacity' => max(0, $this->integer($room['extra_capacity'] ?? $room['extra_bed_capacity'] ?? 0) ?? 0),
                'board_type' => $this->string($room['board_type'] ?? null),
            ];
        }

        return $result;
    }

    private function id(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
        if (is_float($value) && is_finite($value)) {
            return (string) ((int) $value);
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function coordinate(mixed $value, float $limit): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;
        if (!is_finite($coordinate) || abs($coordinate) > $limit) {
            return null;
        }

        return $coordinate;
    }

    private function date(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
