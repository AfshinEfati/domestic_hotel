<?php

namespace App\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Support\Reservation\ReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/** Checks the exact calendar offered to the customer; never searches for another provider. */
class ReservationCreateValidator
{
    public function __construct(
        private readonly RoomCalendarRepositoryInterface $calendars,
        private readonly ProviderRepositoryInterface $providers,
    ) {}

    /** @return array{status:int,error:?string,total:?int,rooms:array<int,array{price:int,provider_id:int}>} */
    public function validate(array $data): array
    {
        $failure = static fn (int $status, string $error): array => [
            'status' => $status, 'error' => $error, 'total' => null, 'rooms' => [],
        ];
        $checkIn = CarbonImmutable::parse($data['check_in']);
        $checkOut = CarbonImmutable::parse($data['check_out']);
        $days = [];
        for ($date = $checkIn; $date->lessThan($checkOut); $date = $date->addDay()) {
            $days[] = $date->toDateString();
        }
        $nights = count($days);
        if ($nights === 0) {
            return $failure(ReservationStatus::RESERVED, 'Invalid stay dates.');
        }

        $results = [];
        $cachedAvailability = [];
        $inventoryUsed = [];

        foreach ($data['hotel']['rooms'] as $index => $selection) {
            $calendar = $this->calendars->find((int) $selection['room_calendar_id']);
            if (!$calendar || (int) $calendar->accommodation_id !== (int) $data['hotel']['accommodation_id']
                || $calendar->day?->toDateString() !== $data['check_in']) {
                return $failure(ReservationStatus::RESERVATION_FAILED, 'Selected calendar is no longer available.');
            }

            $provider = $this->providers->find((int) $calendar->provider_id);
            if (!$provider || !$provider->is_active || !$provider->is_online) {
                return $failure(ReservationStatus::RESERVED, 'Selected provider cannot validate prices online.');
            }
            if (!$calendar->provider_property_id || !$calendar->provider_room_type_id || !$calendar->provider_rate_plan_id) {
                return $failure(ReservationStatus::RESERVED, 'Selected calendar has incomplete provider mappings.');
            }
            $roomType = $calendar->roomType;
            if (!$roomType || $roomType->out_of_service || (int) $roomType->accommodation_id !== (int) $calendar->accommodation_id) {
                return $failure(ReservationStatus::RESERVATION_FAILED, 'Selected room is unavailable.');
            }

            $key = $provider->id . ':' . $calendar->provider_property_id;
            if (!array_key_exists($key, $cachedAvailability)) {
                try {
                    /** @var ProviderAdapterInterface $adapter */
                    $adapter = app()->makeWith(ProviderAdapterInterface::class, ['provider' => $provider]);
                    $cachedAvailability[$key] = $adapter->fetchAvailability(
                        (string) $calendar->provider_property_id, $checkIn, $checkOut
                    );
                } catch (Throwable $exception) {
                    report($exception);
                    return $failure(ReservationStatus::RESERVED, 'Provider price validation failed.');
                }
            }

            $rows = $cachedAvailability[$key]->filter(static fn (array $row): bool =>
                (string) ($row['room_type_id'] ?? '') === (string) $calendar->provider_room_type_id
                && (string) ($row['rate_plan_id'] ?? '') === (string) $calendar->provider_rate_plan_id
            )->keyBy('day');

            $live = collect();
            foreach ($days as $offset => $day) {
                $row = $rows->get($day);
                $inventory = $row['inventory'] ?? null;
                if (!$row || ($row['closed'] ?? false) || $inventory === null || (int) $inventory < 1
                    || !isset($row['daily_rate']) || $row['daily_rate'] === null) {
                    return $failure(ReservationStatus::RESERVATION_FAILED, 'Selected room has no capacity or rate for the full stay.');
                }
                if (($offset === 0 && ($row['cta'] ?? false)) || ($offset === $nights - 1 && ($row['ctd'] ?? false))
                    || (($row['min_stay'] ?? null) !== null && $nights < (int) $row['min_stay'])
                    || (($row['max_stay'] ?? null) !== null && $nights > (int) $row['max_stay'])) {
                    return $failure(ReservationStatus::RESERVATION_FAILED, 'Selected stay is restricted by provider rules.');
                }

                // Inventory belongs to the physical room type, not independently to its rate plans.
                $inventoryKey = $provider->id . ':' . $calendar->room_type_id . ':' . $day;
                $inventoryUsed[$inventoryKey] = ($inventoryUsed[$inventoryKey] ?? 0) + 1;
                if ($inventoryUsed[$inventoryKey] > (int) $inventory) {
                    return $failure(ReservationStatus::RESERVATION_FAILED, 'Insufficient inventory for all selected rooms.');
                }

                $fresh = new RoomCalendar([
                    'day' => $day,
                    'provider_id' => $provider->id,
                    'rack_rate' => $row['rack_rate'] ?? null,
                    'daily_rate' => $row['daily_rate'],
                    'grs_rate' => $row['grs_rate'] ?? null,
                    'extend_bed_daily_rate' => $row['extend_bed_daily_rate'] ?? null,
                ]);
                $live->put($day, $fresh);
            }

            $price = $this->calculateSelectedPrice($roomType, $calendar, $selection['guests'], $live);
            if ($price === null) {
                return $failure(ReservationStatus::RESERVED, 'Cannot calculate the selected room price.');
            }
            $results[$index] = ['price' => $price, 'provider_id' => (int) $provider->id];
        }

        return [
            'status' => ReservationStatus::PURCHASE_QUEUED,
            'error' => null,
            'total' => array_sum(array_column($results, 'price')),
            'rooms' => $results,
        ];
    }

    /** Mirrors AvailabilityFilterService's per-night room/child/extra-bed total. */
    private function calculateSelectedPrice($room, RoomCalendar $selected, array $guests, Collection $live): ?int
    {
        $capacity = (int) $room->capacity;
        if ($capacity < 1) {
            return null;
        }
        $policy = $selected->accommodation?->childPolicy;
        if ($policy && !$policy->status) {
            $policy = null;
        }
        $adult = $child = $infant = 0;
        foreach ($guests as $guest) {
            match ((int) $guest['type']) {
                1 => $adult++, 2 => $child++, 3 => $infant++, default => null,
            };
        }
        $beds = $adult
            + ($policy?->child_service_condition === 'with_service' ? $child : 0)
            + ($policy?->infant_service_condition === 'with_service' ? $infant : 0);
        if ($beds > $capacity + (int) $room->extra_capacity) {
            return null;
        }
        $extra = max(0, $beds - $capacity);
        $total = 0;
        foreach ($live as $day) {
            $base = (int) $day->daily_rate;
            $childRate = $this->policyRate($base, $capacity, $policy?->child_pricing_type ?? 'adult', $policy?->child_pricing_value);
            $infantRate = $this->policyRate($base, $capacity, $policy?->infant_pricing_type ?? 'adult', $policy?->infant_pricing_value);
            if ($childRate === null || $infantRate === null) {
                return null;
            }
            $extraRate = (int) $room->extra_capacity > 0
                ? (int) ($day->extend_bed_daily_rate ?? round($base / $capacity)) : 0;
            $total += $base + $child * $childRate + $infant * $infantRate + $extra * $extraRate;
        }
        return $total;
    }

    private function policyRate(int $base, int $capacity, string $type, ?int $value): ?int
    {
        $person = $base / $capacity;
        return match ($type) {
            'adult' => (int) round($person),
            'free' => 0,
            'half' => (int) round($person / 2),
            'percent' => $value === null ? null : (int) round($person * $value / 100),
            'fixed' => $value,
            default => null,
        };
    }
}
