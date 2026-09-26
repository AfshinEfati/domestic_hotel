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
use Illuminate\Validation\ValidationException;
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

            $nightly = $this->calculateSelectedPrice(
                $roomType,
                $calendar,
                $roomSelection['guests'],
                $days,
                $live,
                $checkIn,
            );
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
     * Validate the selected hotel/room against the actual guest configuration before
     * creating any reservation rows. This is local business validation and must not
     * depend on the external provider validation step.
     *
     * @throws ValidationException
     */
    public function validateGuestSelection(array $data): void
    {
        $errors = [];
        $checkIn = CarbonImmutable::parse($data['check_in'])->startOfDay();
        $accommodationId = (int) $data['hotel']['accommodation_id'];

        foreach ($data['hotel']['rooms'] as $index => $roomSelection) {
            $roomKey = "hotel.rooms.$index.guests";
            $calendarEntry = collect($roomSelection['calendar'] ?? [])
                ->firstWhere('date', $data['check_in']);

            if ($calendarEntry === null) {
                $errors[$roomKey][] = 'Selected room does not contain a calendar entry for check-in date.';
                continue;
            }

            $calendar = $this->calendars->find((int) $calendarEntry['calendar_id']);
            if ($calendar === null) {
                $errors[$roomKey][] = 'Selected room calendar is no longer available.';
                continue;
            }
            if ((int) $calendar->accommodation_id !== $accommodationId) {
                $errors[$roomKey][] = 'Selected room does not belong to the selected hotel.';
                continue;
            }
            if ($calendar->day?->toDateString() !== $checkIn->toDateString()) {
                $errors[$roomKey][] = 'Selected room calendar does not match the check-in date.';
                continue;
            }

            $room = $calendar->roomType;
            if (!$room || (int) $room->accommodation_id !== $accommodationId || $room->out_of_service) {
                $errors[$roomKey][] = 'Selected room is not available in the selected hotel.';
                continue;
            }

            $policy = $calendar->accommodation?->childPolicy;
            if ($policy && !$policy->status) {
                $policy = null;
            }

            $plan = $this->buildGuestPlan(
                $room,
                $roomSelection['guests'] ?? [],
                $policy,
                $checkIn,
            );

            if ($plan === null) {
                $errors[$roomKey][] = sprintf(
                    'Selected guests do not fit this room after applying guest ages, hotel child policy and service conditions. Room capacity is %d + %d extra.',
                    (int) $room->capacity,
                    (int) $room->extra_capacity,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Build the occupancy/pricing plan from birthdays and the active hotel child policy.
     *
     * Important business rules:
     * - request guest.type and guest.age never decide child/adult treatment;
     * - age is calculated at check-in;
     * - normal room capacity is already paid for, so children/infants that fit in
     *   unused base capacity do not add a child/infant charge;
     * - child-policy coverage limits are applied only to child/infant guests that
     *   are beyond normal room capacity;
     * - an extra child/infant outside the policy allowance is treated as a full
     *   adult-priced/service guest.
     *
     * @return array{
     *     adult_count:int,
     *     base_child_count:int,
     *     covered_child_extra:int,
     *     covered_infant_extra:int,
     *     uncovered_child_extra:int,
     *     adult_extra:int,
     *     extra_bed_count:int
     * }|null
     */
    private function buildGuestPlan(
        $room,
        array $guests,
        ?HotelChildPolicy $policy,
        CarbonImmutable $checkIn
    ): ?array {
        $capacity = (int) $room->capacity;
        $extraCapacity = max(0, (int) $room->extra_capacity);

        if ($capacity < 1 || $guests === []) {
            return null;
        }

        $adultCount = 0;
        $childCandidates = [];

        foreach ($guests as $index => $guest) {
            $age = $this->calculateGuestAge($guest['birthday'] ?? null, $checkIn);

            // Unknown birthday, no active policy, or age outside child-policy ranges
            // means full adult treatment. The request type is intentionally ignored.
            if ($age === null || $policy === null) {
                $adultCount++;
                continue;
            }

            $infantEligible = (int) $policy->max_infant_age > 0
                && $age < (int) $policy->max_infant_age;

            // The exact infant upper boundary belongs to child range. This avoids
            // losing age == max_infant_age between infant and child categories.
            $childEligible = (int) $policy->max_child_age > 0
                && $age <= (int) $policy->max_child_age;

            if (!$infantEligible && !$childEligible) {
                $adultCount++;
                continue;
            }

            $kind = $infantEligible ? 'infant' : 'child';
            $pricingType = $kind === 'infant'
                ? $policy->infant_pricing_type
                : $policy->child_pricing_type;
            $pricingValue = $kind === 'infant'
                ? $policy->infant_pricing_value
                : $policy->child_pricing_value;

            $childCandidates[] = [
                'index' => $index,
                'age' => $age,
                'kind' => $kind,
                'child_eligible' => $childEligible,
                'discount' => $this->policyDiscountPriority($pricingType, $pricingValue),
            ];
        }

        $adultExtra = max(0, $adultCount - $capacity);
        $baseSlotsForChildren = max(0, $capacity - $adultCount);
        $extraChildCount = max(0, count($childCandidates) - $baseSlotsForChildren);

        // Guests that must be outside normal room capacity should be the guests with
        // the best applicable child-policy discount. This keeps base room capacity
        // from consuming a free/half-rate entitlement while a more expensive child
        // is left outside it.
        usort($childCandidates, static function (array $a, array $b): int {
            return ($b['discount'] <=> $a['discount'])
                ?: ($a['age'] <=> $b['age'])
                ?: ($a['index'] <=> $b['index']);
        });

        $extraCandidates = array_slice($childCandidates, 0, $extraChildCount);

        $coveredChildExtra = 0;
        $coveredInfantExtra = 0;
        $uncoveredChildExtra = 0;
        $coveredTotal = 0;
        $coveredInfants = 0;
        $coveredServiceBeds = 0;

        foreach ($extraCandidates as $candidate) {
            $sharedLimitAvailable = $policy !== null
                && (
                    $policy->max_children_covered === null
                    || $coveredTotal < (int) $policy->max_children_covered
                );

            if (!$sharedLimitAvailable) {
                $uncoveredChildExtra++;
                continue;
            }

            if ($candidate['kind'] === 'infant') {
                $infantLimitAvailable = $policy->max_infants_covered === null
                    || $coveredInfants < (int) $policy->max_infants_covered;

                if ($infantLimitAvailable) {
                    $coveredInfantExtra++;
                    $coveredInfants++;
                    $coveredTotal++;

                    if ($policy->infant_service_condition === 'with_service') {
                        $coveredServiceBeds++;
                    }
                    continue;
                }

                if (
                    $policy->infant_when_disabled === 'as_child'
                    && $candidate['child_eligible']
                ) {
                    $coveredChildExtra++;
                    $coveredTotal++;

                    if ($policy->child_service_condition === 'with_service') {
                        $coveredServiceBeds++;
                    }
                    continue;
                }

                $uncoveredChildExtra++;
                continue;
            }

            $coveredChildExtra++;
            $coveredTotal++;

            if ($policy->child_service_condition === 'with_service') {
                $coveredServiceBeds++;
            }
        }

        // Adults beyond base capacity and children outside policy allowance require
        // full extra service. Covered child/infant guests require an extra bed only
        // when the hotel's policy explicitly says "with_service".
        $extraBedCount = $adultExtra + $uncoveredChildExtra + $coveredServiceBeds;

        if ($extraBedCount > $extraCapacity) {
            return null;
        }

        return [
            'adult_count' => $adultCount,
            'base_child_count' => count($childCandidates) - $extraChildCount,
            'covered_child_extra' => $coveredChildExtra,
            'covered_infant_extra' => $coveredInfantExtra,
            'uncovered_child_extra' => $uncoveredChildExtra,
            'adult_extra' => $adultExtra,
            'extra_bed_count' => $extraBedCount,
        ];
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
    private function calculateSelectedPrice(
        $room,
        RoomCalendar $selected,
        array $guests,
        array $days,
        Collection $live,
        CarbonImmutable $checkIn
    ): ?array {
        $capacity = (int) $room->capacity;
        if ($capacity < 1) {
            return null;
        }

        $policy = $selected->accommodation?->childPolicy;
        if ($policy && !$policy->status) {
            $policy = null;
        }

        $plan = $this->buildGuestPlan($room, $guests, $policy, $checkIn);
        if ($plan === null) {
            return null;
        }

        $nightly = [];

        foreach ($days as $day) {
            $calendar = $live->get($day);
            if (!$calendar || $calendar->daily_rate === null) {
                return null;
            }

            $base = (int) $calendar->daily_rate;

            $childRate = $this->policyRate(
                $base,
                $capacity,
                $policy?->child_pricing_type ?? 'adult',
                $policy?->child_pricing_value
            );
            $infantRate = $this->policyRate(
                $base,
                $capacity,
                $policy?->infant_pricing_type ?? 'adult',
                $policy?->infant_pricing_value
            );

            if (
                ($plan['covered_child_extra'] > 0 && $childRate === null)
                || ($plan['covered_infant_extra'] > 0 && $infantRate === null)
            ) {
                return null;
            }

            $childRate ??= 0;
            $infantRate ??= 0;

            $extraRate = $plan['extra_bed_count'] > 0
                ? (int) (
                    $calendar->extend_bed_daily_rate
                    ?? round($base / $capacity)
                )
                : 0;

            $total = $base
                + $plan['covered_child_extra'] * $childRate
                + $plan['covered_infant_extra'] * $infantRate
                + $plan['extra_bed_count'] * $extraRate;

            $nightly[] = [
                'date' => $day,
                'price' => $total,
            ];
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
