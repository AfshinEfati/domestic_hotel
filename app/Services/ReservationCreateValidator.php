<?php

namespace App\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Support\Reservation\ReservationGuestType;
use App\Support\Reservation\ReservationStatus;
use Carbon\CarbonImmutable;
use Error;
use Illuminate\Support\Collection;
use Throwable;

/** Checks the exact calendars offered to the customer, night by night; never searches for another provider. */
readonly class ReservationCreateValidator
{
    public function __construct(
        private RoomCalendarRepositoryInterface $calendars,
        private ProviderRepositoryInterface     $providers,
    ) {}

    /**
     * @return array{
     *     status:int,
     *     error:?string,
     *     total:?int,
     *     rooms:array<int,array{price:int,provider_id:int,nights:array<int,array{date:string,price:int}>}>
     * }
     */
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
            return $failure(ReservationStatus::CHECKED, 'Invalid stay dates.');
        }

        $accommodationId = (int) $data['hotel']['accommodation_id'];

        // Resolve and validate every night's calendar before contacting any provider:
        // a reservation cannot mix hotels, and every room must offer one consistent
        // provider/room type/rate plan across its whole stay.
        $selectedCalendarsByRoom = [];
        foreach ($data['hotel']['rooms'] as $index => $roomSelection) {
            $byDate = [];
            foreach ($roomSelection['calendar'] as $night) {
                $calendar = $this->calendars->find((int) $night['calendar_id']);
                if ($calendar === null || $calendar->day?->toDateString() !== $night['date']) {
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected calendar is no longer available for its night.');
                }
                if ((int) $calendar->accommodation_id !== $accommodationId) {
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected calendar does not belong to the requested hotel.');
                }
                $byDate[$night['date']] = $calendar;
            }

            $first = $byDate[$days[0]];
            foreach ($byDate as $calendar) {
                if ((int) $calendar->provider_id !== (int) $first->provider_id
                    || (int) $calendar->room_type_id !== (int) $first->room_type_id
                    || (int) $calendar->rate_plan_id !== (int) $first->rate_plan_id) {
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected calendars for a room must share one offer across every night.');
                }
            }

            $selectedCalendarsByRoom[$index] = $byDate;
        }

        $results = [];
        $cachedAvailability = [];
        $inventoryUsed = [];

        foreach ($data['hotel']['rooms'] as $index => $roomSelection) {
            $byDate = $selectedCalendarsByRoom[$index];
            $calendar = $byDate[$days[0]];
            $provider = $this->providers->find((int) $calendar->provider_id);
            if (!$provider || !$provider->is_active || !$provider->is_online) {
                return $failure(ReservationStatus::CHECKED, 'Selected provider cannot validate prices online.');
            }
            if (!$calendar->provider_property_id || !$calendar->provider_room_type_id || !$calendar->provider_rate_plan_id) {
                return $failure(ReservationStatus::CHECKED, 'Selected calendar has incomplete provider mappings.');
            }
            $roomType = $calendar->roomType;
            if (!$roomType || $roomType->out_of_service || (int) $roomType->accommodation_id !== $accommodationId) {
                return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected room is unavailable.');
            }

            // Guest type (child/infant/adult) is resolved from age inside calculateSelectedPrice
            // below, not validated here — see normalizeGuestCounts for why.

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
                    return $failure(ReservationStatus::CHECKED, 'Provider price validation failed.');
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
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected room has no capacity or rate for the full stay.');
                }
                if (($offset === 0 && ($row['cta'] ?? false)) || ($offset === $nights - 1 && ($row['ctd'] ?? false))
                    || (($row['min_stay'] ?? null) !== null && $nights < (int) $row['min_stay'])
                    || (($row['max_stay'] ?? null) !== null && $nights > (int) $row['max_stay'])) {
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Selected stay is restricted by provider rules.');
                }

                // Inventory belongs to the physical room type, not independently to its rate plans.
                $inventoryKey = $provider->id . ':' . $calendar->room_type_id . ':' . $day;
                $inventoryUsed[$inventoryKey] = ($inventoryUsed[$inventoryKey] ?? 0) + 1;
                if ($inventoryUsed[$inventoryKey] > (int) $inventory) {
                    return $failure(ReservationStatus::NO_AVAILABILITY, 'Insufficient inventory for all selected rooms.');
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

            $nightly = $this->calculateSelectedPrice($roomType, $calendar, $roomSelection['guests'], $days, $live);
            if ($nightly === null) {
                return $failure(ReservationStatus::CHECKED, 'Cannot calculate the selected room price.');
            }
            $results[$index] = [
                'price' => array_sum(array_column($nightly, 'price')),
                'provider_id' => (int) $provider->id,
                'nights' => $nightly,
            ];
        }

        return [
            'status' => ReservationStatus::READY_FOR_PAYMENT,
            'error' => null,
            'total' => array_sum(array_column($results, 'price')),
            'rooms' => $results,
        ];
    }


    /**
     * @param array $guests
     * @param HotelChildPolicy|null $policy
     * @param CarbonImmutable $checkIn
     * @return int[]
     */
    private function normalizeGuestCounts(array $guests, ?HotelChildPolicy $policy, CarbonImmutable $checkIn): array
    {
        $adult = 0;
        $candidates = [];

        foreach ($guests as $index => $guest) {
            $age = $this->calculateGuestAge($guest['birthday'] ?? null, $checkIn);
            if ($age === null) {
                $adult++;
                continue;
            }

            // No active child policy for this hotel: nobody gets a discount or a bed exemption.
            if ($policy === null) {
                $adult++;
                continue;
            }

            $age = $this->calculateGuestAge($guest['birthday'] ?? null, $checkIn);
            // Age must be known to grant any non-adult treatment; unknown age defaults to adult.
            if ($age === null) {
                $adult++;
                continue;
            }

            $infantEligible = (int) $policy->max_infant_age > 0 && $age < (int) $policy->max_infant_age;
            $childEligible = (int) $policy->max_child_age > 0
                && $age > (int) $policy->max_infant_age
                && $age <= (int) $policy->max_child_age;

            if (!$infantEligible && !$childEligible) {
                $adult++;
                continue;
            }

            $candidates[] = [
                'index' => $index,
                'age' => $age,
                'infant_eligible' => $infantEligible,
                'child_eligible' => $childEligible,
                'discount' => $this->policyDiscountPriority(
                    $infantEligible ? $policy->infant_pricing_type : $policy->child_pricing_type,
                    $infantEligible ? $policy->infant_pricing_value : $policy->child_pricing_value,
                ),
            ];
        }

        // For a shared cap, prioritize the larger known discount so that guest ordering
        // in the request cannot make a free infant lose a covered slot to a half-rate child.
        usort($candidates, static function (array $a, array $b): int {
            return ($b['discount'] <=> $a['discount'])
                ?: ($a['age'] <=> $b['age'])
                    ?: ($a['index'] <=> $b['index']);
        });

        $child = 0;
        $infant = 0;
        $coveredTotal = 0;
        $coveredInfants = 0;

        foreach ($candidates as $candidate) {
            // Exhausting the shared allowance always means adult pricing.
            // Never cascade a second infant into a half-rate child here.
            if ($policy->max_children_covered !== null && $coveredTotal >= (int) $policy->max_children_covered) {
                $adult++;
                continue;
            }

            if ($candidate['infant_eligible']) {
                $infantLimitAvailable = $policy->max_infants_covered === null
                    || $coveredInfants < (int) $policy->max_infants_covered;

                if ($infantLimitAvailable) {
                    $infant++;
                    $coveredInfants++;
                    $coveredTotal++;
                    continue;
                }

                // The infant-only cap is full, but the shared child cap may
                // still have room for this infant to use the child policy.
                if ($policy->infant_when_disabled === 'as_child' && $candidate['child_eligible']) {
                    $child++;
                    $coveredTotal++;
                    continue;
                }

                $adult++;
                continue;
            }

            $child++;
            $coveredTotal++;
        }

        return [$adult, $child, $infant];
    }

    private function calculateGuestAge(?string $birthday, CarbonImmutable $reference): ?int
    {
        if (!$birthday) {
            return null;
        }
        $birth = CarbonImmutable::createFromFormat('Y-m-d', $birthday);
        if (!$birth instanceof CarbonImmutable) {
            return null;
        }
        return max(0, (int) $birth->diffInYears($reference));
    }

    /**
     * Only relative-rate discounts can be ordered without knowing the room rate.
     * Fixed child amounts have no universal discount ordering: they use stable age/order.
     */
    private function policyDiscountPriority(?string $type, ?int $value): float
    {
        return match ($type) {
            'free' => 1.0,
            'half' => 0.5,
            'percent' => $value === null ? 0.0 : 1.0 - $value / 100.0,
            default => 0.0,
        };
    }

    /**
     * Mirrors AvailabilityFilterService's per-night room/child/extra-bed total.
     * @return array<int,array{date:string,price:int}>|null
     */
    private function calculateSelectedPrice($room, RoomCalendar $selected, array $guests, array $days, Collection $live): ?array
    {
        $capacity = (int) $room->capacity;
        if ($capacity < 1) {
            return null;
        }
        $policy = $selected->accommodation?->childPolicy;
        if ($policy && !$policy->status) {
            $policy = null;
        }
        // Age is evaluated at reservation creation time. The client-provided age and
        // guest type are never trusted for pricing or capacity decisions.
        $reservationDate = CarbonImmutable::now('Asia/Tehran')->startOfDay();
        [$adult, $child, $infant] = $this->normalizeGuestCounts($guests, $policy, $reservationDate);
        // The room base price already covers the room's normal capacity.
        // A covered child/infant occupying an existing bed must not create a
        // second charge. Child/infant policy pricing applies only to guests
        // that exceed the room's normal capacity and therefore need extra service.
        $guestCount = $adult + $child + $infant;
        if ($guestCount > $capacity + (int) $room->extra_capacity) {
            return null;
        }
        $extra = max(0, $guestCount - $capacity);

        // Adults consume normal room capacity first. Remaining normal beds are
        // then occupied by covered children/infants without a separate charge.
        $remainingAfterAdults = max(0, $capacity - $adult);
        $childIncludedInBase = min($child, $remainingAfterAdults);
        $remainingAfterChildren = max(0, $remainingAfterAdults - $childIncludedInBase);
        $infantIncludedInBase = min($infant, $remainingAfterChildren);
        $pricedChild = $child - $childIncludedInBase;
        $pricedInfant = $infant - $infantIncludedInBase;
        $nightly = [];
        foreach ($days as $day) {
            $calendar = $live->get($day);
            $base = (int) $calendar->daily_rate;
            $childRate = $this->policyRate($base, $capacity, $policy?->child_pricing_type ?? 'adult', $policy?->child_pricing_value);
            $infantRate = $this->policyRate($base, $capacity, $policy?->infant_pricing_type ?? 'adult', $policy?->infant_pricing_value);
            if ($childRate === null || $infantRate === null) {
                return null;
            }
            $extraRate = (int) $room->extra_capacity > 0
                ? (int) ($calendar->extend_bed_daily_rate ?? round($base / $capacity)) : 0;
            $total = $base
                + $pricedChild * $childRate
                + $pricedInfant * $infantRate
                + $extra * $extraRate;
            $nightly[] = ['date' => $day, 'price' => $total];
        }
        return $nightly;
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
