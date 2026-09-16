<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailabilityFilterService
{
    public function __construct(
        private readonly RoomCalendarRepositoryInterface $roomCalendarRepository,
    ) {}

    public function filterAvailability(array $data): Collection
    {
        $checkIn = Carbon::createFromFormat('Y-m-d', $data['check_in']);
        $checkOut = Carbon::createFromFormat('Y-m-d', $data['check_out']);

        $dates = [];
        $cursor = $checkIn->copy();

        while ($cursor->lt($checkOut)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        if ($dates === []) {
            return collect();
        }

        $city = $data['city'];

        $accommodations = Accommodation::query()
            ->whereHas('city', function (Builder $query) use ($city) {
                $query
                    ->where('en_name', 'like', '%' . strtolower($city) . '%')
                    ->orWhere('fa_name', 'like', '%' . $city . '%');
            })
            ->with(['childPolicy', 'rooms.roomTypeName', 'rules'])
            ->where('is_active', true)
            ->get();

        if ($accommodations->isEmpty()) {
            return collect();
        }

        $calendars = $this->roomCalendarRepository->getForAvailability(
            $accommodations->modelKeys(),
            $checkIn->toDateString(),
            $checkOut->toDateString()
        )->groupBy('accommodation_id');

        $result = collect();

        foreach ($accommodations as $accommodation) {
            $hotelCalendars = $calendars->get($accommodation->id, collect());

            $rooms = $this->findCheapestAvailableCombination(
                $accommodation,
                $data['rooms'],
                $dates,
                $hotelCalendars
            );

            // Every requested room must be available in the same hotel.
            if ($rooms->count() !== count($data['rooms'])) {
                continue;
            }

            $accommodation->setAttribute('available_rooms', $rooms);
            $result->push($accommodation);
        }

        return $result;
    }

    private function findCheapestAvailableCombination(
        Accommodation $accommodation,
        array $requestedRooms,
        array $dates,
        Collection $hotelCalendars
    ): Collection {
        // AvailabilityRequest also rejects more than five rooms; defend direct callers.
        if ($requestedRooms === [] || count($requestedRooms) > 5 || $dates === []) {
            return collect();
        }

        $policy = $accommodation->childPolicy;

        if ($policy !== null && !$policy->status) {
            $policy = null;
        }

        $requests = [];

        foreach ($requestedRooms as $requestIndex => $requestedRoom) {
            $passengers = $this->normalizePassengers(
                $requestedRoom['passengers'] ?? [],
                $policy
            );

            if ($passengers === []) {
                return collect();
            }

            $counts = ['adult' => 0, 'child' => 0, 'infant' => 0];

            foreach ($passengers as $passenger) {
                $counts[$passenger['type']]++;
            }

            // Keep every requested room independent, even when compositions match.
            // Identical compositions can reuse priced options, not a single assignment.
            $requests[] = [
                'index' => $requestIndex,
                'counts' => $counts,
                'signature' => implode(':', [
                    $counts['adult'],
                    $counts['child'],
                    $counts['infant'],
                ]),
                'options' => [],
            ];
        }

        $calendarsByRoom = $hotelCalendars->groupBy('room_type_id');
        $optionsBySignature = [];

        foreach ($requests as &$request) {
            $signature = $request['signature'];

            if (isset($optionsBySignature[$signature])) {
                $request['options'] = $optionsBySignature[$signature];
                continue;
            }

            foreach ($accommodation->rooms as $room) {
                if ($room->out_of_service || $room->capacity <= 0) {
                    continue;
                }

                $roomCalendars = $calendarsByRoom->get($room->id, collect());

                // An offer keeps the same provider and rate plan on every night.
                $offers = $roomCalendars->groupBy(
                    fn (RoomCalendar $calendar) =>
                        $calendar->provider_id . ':' . $calendar->rate_plan_id
                );

                foreach ($offers as $offerCalendars) {
                    $byDay = $offerCalendars->keyBy(
                        fn (RoomCalendar $calendar) =>
                            $calendar->day->format('Y-m-d')
                    );

                    if ($byDay->count() !== count($dates)) {
                        continue;
                    }

                    $inventoryByDay = [];
                    $valid = true;

                    foreach ($dates as $day) {
                        $calendar = $byDay->get($day);

                        if (
                            $calendar === null
                            || $calendar->closed
                            || $calendar->inventory === null
                            || $calendar->inventory < 1
                            || $calendar->daily_rate === null
                        ) {
                            $valid = false;
                            break;
                        }

                        $inventoryByDay[$day] = (int) $calendar->inventory;
                    }

                    if (!$valid) {
                        continue;
                    }

                    $priceData = $this->priceOption(
                        $room,
                        $request['counts'],
                        $policy,
                        $dates,
                        $byDay
                    );

                    if ($priceData === null) {
                        continue;
                    }

                    $firstCalendar = $byDay->get($dates[0]);

                    $request['options'][] = [
                        'room' => $room,
                        'price' => $priceData,
                        'provider_id' => (int) $firstCalendar->provider_id,
                        'rate_plan_id' => (int) $firstCalendar->rate_plan_id,
                        'rate_plan' => $firstCalendar->ratePlan,
                        'inventory_by_day' => $inventoryByDay,
                        'min_inventory' => min($inventoryByDay),
                    ];
                }
            }

            if ($request['options'] === []) {
                return collect();
            }

            // Try the lowest full-stay price first, not the lowest nightly share.
            usort(
                $request['options'],
                static function (array $a, array $b): int {
                    return ($a['price']['total_price'] <=> $b['price']['total_price'])
                        ?: ($a['room']->id <=> $b['room']->id)
                        ?: ($a['provider_id'] <=> $b['provider_id'])
                        ?: ($a['rate_plan_id'] <=> $b['rate_plan_id']);
                }
            );

            $optionsBySignature[$signature] = $request['options'];
        }

        unset($request);

        // Constrained compositions go first; request indexes are restored below.
        usort(
            $requests,
            static fn (array $a, array $b): int =>
                (count($a['options']) <=> count($b['options']))
                ?: ($a['index'] <=> $b['index'])
        );

        // Lower bound on the cost of the unassigned requests for pruning.
        $minimumRemaining = array_fill(0, count($requests) + 1, 0);

        for ($i = count($requests) - 1; $i >= 0; $i--) {
            $minimumRemaining[$i] = $minimumRemaining[$i + 1]
                + $requests[$i]['options'][0]['price']['total_price'];
        }

        $bestCost = PHP_INT_MAX;
        $bestSelection = [];

        $search = function (
            int $depth,
            int $cost,
            array $used,
            array $limits,
            array $selection
        ) use (
            &$search,
            &$bestCost,
            &$bestSelection,
            $requests,
            $dates,
            $minimumRemaining
        ): void {
            if ($depth === count($requests)) {
                if ($cost < $bestCost) {
                    $bestCost = $cost;
                    $bestSelection = $selection;
                }
                return;
            }

            if ($cost + $minimumRemaining[$depth] >= $bestCost) {
                return;
            }

            $request = $requests[$depth];

            foreach ($request['options'] as $option) {
                $roomId = (int) $option['room']->id;
                $newUsed = ($used[$roomId] ?? 0) + 1;
                $newLimits = $limits;
                $valid = true;

                foreach ($dates as $day) {
                    // Rate plans/providers cannot independently spend the same
                    // physical room-type inventory on a given night.
                    $limit = min(
                        $limits[$roomId][$day] ?? PHP_INT_MAX,
                        $option['inventory_by_day'][$day]
                    );

                    if ($newUsed > $limit) {
                        $valid = false;
                        break;
                    }

                    $newLimits[$roomId][$day] = $limit;
                }

                if (!$valid) {
                    continue;
                }

                $newUsedByRoom = $used;
                $newUsedByRoom[$roomId] = $newUsed;
                $newSelection = $selection;
                $newSelection[$request['index']] = $option;

                $search(
                    $depth + 1,
                    $cost + $option['price']['total_price'],
                    $newUsedByRoom,
                    $newLimits,
                    $newSelection
                );
            }
        };

        $search(0, 0, [], [], []);

        if (count($bestSelection) !== count($requestedRooms)) {
            return collect();
        }

        // Each returned row carries the actual total quantity selected for its
        // room type, even when the request is fulfilled by mixed room types.
        $requiredByRoomId = [];
        foreach ($bestSelection as $option) {
            $roomId = (int) $option['room']->id;
            $requiredByRoomId[$roomId] = ($requiredByRoomId[$roomId] ?? 0) + 1;
        }

        $selectedRooms = [];

        foreach ($bestSelection as $requestIndex => $option) {
            $room = clone $option['room'];
            $room->setAttribute('pricing', $option['price']['pricing']);
            $room->setAttribute('total_price', $option['price']['total_price']);
            $room->setAttribute('nightly_prices', $option['price']['nightly_prices']);
            $room->setAttribute('ratePlan', $option['rate_plan']);
            $room->setAttribute('provider_id', $option['provider_id']);
            $room->setAttribute('available_inventory', $option['min_inventory']);
            $room->setAttribute('required_inventory', $requiredByRoomId[(int) $room->id]);
            $room->setAttribute('requested_room_index', $requestIndex);
            $room->setAttribute('extra_bed_count', $option['price']['extra_bed_count']);
            $selectedRooms[$requestIndex] = $room;
        }

        ksort($selectedRooms);

        return collect(array_values($selectedRooms));
    }

    /**
     * max_children_covered is the shared number of children/infants entitled
     * to a child-policy rate; max_infants_covered is a further infant-only cap.
     * A null cap means that cap itself is absent, not that other caps are ignored.
     */
    private function normalizePassengers(
        array $passengers,
        ?HotelChildPolicy $policy
    ): array {
        if ($passengers === []) {
            return [];
        }

        $normalized = [];
        $candidates = [];

        foreach ($passengers as $index => $passenger) {
            $type = match ($passenger['type'] ?? null) {
                'adl', 'adult' => 'adult',
                'chd', 'child' => 'child',
                'inf', 'infant' => 'infant',
                default => null,
            };

            if ($type === null) {
                return [];
            }

            // An explicitly declared adult must never become an infant based on age.
            if ($type === 'adult') {
                $normalized[$index] = ['type' => 'adult'];
                continue;
            }

            if (!isset($passenger['age'])) {
                return [];
            }

            $age = (int) $passenger['age'];
            $normalized[$index] = ['type' => 'adult'];

            if ($policy === null) {
                continue;
            }

            $infantEligible = (int) $policy->max_infant_age > 0
                && $age < (int) $policy->max_infant_age;
            $childEligible = (int) $policy->max_child_age > 0
                && $age <= (int) $policy->max_child_age;

            if (!$infantEligible && !$childEligible) {
                continue;
            }

            $pricingType = $infantEligible
                ? $policy->infant_pricing_type
                : $policy->child_pricing_type;
            $pricingValue = $infantEligible
                ? $policy->infant_pricing_value
                : $policy->child_pricing_value;

            $candidates[] = [
                'index' => $index,
                'age' => $age,
                'infant_eligible' => $infantEligible,
                'child_eligible' => $childEligible,
                'discount' => $this->policyDiscountPriority($pricingType, $pricingValue),
            ];
        }

        // For a shared cap, prioritize the larger known discount so that
        // request passenger ordering cannot make a free infant lose to a half-rate child.
        usort($candidates, static function (array $a, array $b): int {
            return ($b['discount'] <=> $a['discount'])
                ?: ($a['age'] <=> $b['age'])
                ?: ($a['index'] <=> $b['index']);
        });

        $coveredTotal = 0;
        $coveredInfants = 0;

        foreach ($candidates as $candidate) {
            $index = $candidate['index'];

            // Exhausting the shared allowance always means adult pricing.
            // Never cascade a second infant into a half-rate child here.
            if (
                $policy->max_children_covered !== null
                && $coveredTotal >= (int) $policy->max_children_covered
            ) {
                continue;
            }

            if ($candidate['infant_eligible']) {
                $infantLimitAvailable = $policy->max_infants_covered === null
                    || $coveredInfants < (int) $policy->max_infants_covered;

                if ($infantLimitAvailable) {
                    $normalized[$index] = ['type' => 'infant'];
                    $coveredInfants++;
                    $coveredTotal++;
                    continue;
                }

                // The infant-only cap is full, but the shared child cap may
                // still have room for this infant to use the child policy.
                if ($policy->infant_when_disabled !== 'as_child') {
                    continue;
                }
            }

            if ($candidate['child_eligible']) {
                $normalized[$index] = ['type' => 'child'];
                $coveredTotal++;
            }
        }

        ksort($normalized);

        return array_values($normalized);
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

    private function priceOption(
        RoomType $room,
        array $counts,
        ?HotelChildPolicy $policy,
        array $dates,
        Collection $calendars
    ): ?array {
        $capacity = (int) $room->capacity;
        $extraCapacity = (int) $room->extra_capacity;

        if ($capacity <= 0) {
            return null;
        }

        $adultCount = $counts['adult'];
        $childCount = $counts['child'];
        $infantCount = $counts['infant'];

        $childNeedsBed = $policy !== null
            && $policy->child_service_condition === 'with_service';
        $infantNeedsBed = $policy !== null
            && $policy->infant_service_condition === 'with_service';

        $requiredBeds = $adultCount
            + ($childNeedsBed ? $childCount : 0)
            + ($infantNeedsBed ? $infantCount : 0);

        if ($requiredBeds > $capacity + $extraCapacity) {
            return null;
        }

        $extraBedCount = max(0, $requiredBeds - $capacity);
        $baseAdultCount = min($adultCount, $capacity);

        $roomBaseTotal = 0;
        $childTotal = 0;
        $infantTotal = 0;
        $extraTotal = 0;
        $adultUnitTotal = 0;
        $childUnitTotal = 0;
        $infantUnitTotal = 0;
        $extraUnitTotal = 0;
        $nightlyPrices = [];

        foreach ($dates as $day) {
            $calendar = $calendars->get($day);

            if ($calendar === null || $calendar->daily_rate === null) {
                return null;
            }

            $basePrice = (int) $calendar->daily_rate;

            $childDaily = $this->policyRate(
                $basePrice,
                $capacity,
                $policy?->child_pricing_type ?? 'adult',
                $policy?->child_pricing_value
            );

            $infantDaily = $this->policyRate(
                $basePrice,
                $capacity,
                $policy?->infant_pricing_type ?? 'adult',
                $policy?->infant_pricing_value
            );

            if (
                ($childCount > 0 && $childDaily === null)
                || ($infantCount > 0 && $infantDaily === null)
            ) {
                return null;
            }

            $childDaily ??= 0;
            $infantDaily ??= 0;

            $extraUnit = $extraCapacity > 0
                ? (int) ($calendar->extend_bed_daily_rate ?? round($basePrice / $capacity))
                : 0;

            $nightChildTotal = $childDaily * $childCount;
            $nightInfantTotal = $infantDaily * $infantCount;
            $nightExtraTotal = $extraUnit * $extraBedCount;
            $nightTotal = $basePrice + $nightChildTotal + $nightInfantTotal + $nightExtraTotal;

            $adultShare = $baseAdultCount > 0
                ? intdiv($basePrice, $baseAdultCount)
                : 0;
            $adultShareRemainder = $baseAdultCount > 0
                ? $basePrice % $baseAdultCount
                : 0;

            $nightlyPrices[] = [
                'date' => $day,
                'calendar_id' => $calendar->id,
                'provider_id' => $calendar->provider_id,
                'rate_plan_id' => $calendar->rate_plan_id,
                'room_base_price' => $basePrice,
                'extra_price' => $extraUnit,
                'extra_count' => $extraBedCount,
                'extra_total' => $nightExtraTotal,
                'child_total' => $nightChildTotal,
                'infant_total' => $nightInfantTotal,
                'total_price' => $nightTotal,
                'adult' => [
                    'rack_rate' => $calendar->rack_rate,
                    'daily_rate' => $calendar->daily_rate,
                    'grs_rate' => $calendar->grs_rate,
                    'adult_price' => $adultShare,
                    'base_adult_count' => $baseAdultCount,
                    'base_share_remainder' => $adultShareRemainder,
                ],
                'child' => [
                    'rack_rate' => $this->policyRate(
                        $calendar->rack_rate,
                        $capacity,
                        $policy?->child_pricing_type ?? 'adult',
                        $policy?->child_pricing_value
                    ),
                    'daily_rate' => $childDaily,
                    'grs_rate' => $this->policyRate(
                        $calendar->grs_rate,
                        $capacity,
                        $policy?->child_pricing_type ?? 'adult',
                        $policy?->child_pricing_value
                    ),
                    'child_price' => $childDaily,
                    'count' => $childCount,
                ],
                'infant' => [
                    'rack_rate' => $this->policyRate(
                        $calendar->rack_rate,
                        $capacity,
                        $policy?->infant_pricing_type ?? 'adult',
                        $policy?->infant_pricing_value
                    ),
                    'daily_rate' => $infantDaily,
                    'grs_rate' => $this->policyRate(
                        $calendar->grs_rate,
                        $capacity,
                        $policy?->infant_pricing_type ?? 'adult',
                        $policy?->infant_pricing_value
                    ),
                    'infant_price' => $infantDaily,
                    'count' => $infantCount,
                ],
            ];

            $roomBaseTotal += $basePrice;
            $childTotal += $nightChildTotal;
            $infantTotal += $nightInfantTotal;
            $extraTotal += $nightExtraTotal;
            $adultUnitTotal += $adultShare;
            $childUnitTotal += $childDaily;
            $infantUnitTotal += $infantDaily;
            $extraUnitTotal += $extraUnit;
        }

        $totalPrice = $roomBaseTotal + $childTotal + $infantTotal + $extraTotal;

        return [
            'total_price' => $totalPrice,
            'extra_bed_count' => $extraBedCount,
            'pricing' => [
                'adult' => $adultUnitTotal,
                'child' => $childUnitTotal,
                'infant' => $infantUnitTotal,
                'extra_price' => $extraUnitTotal,
                'room_base_price' => $roomBaseTotal,
                'child_total' => $childTotal,
                'infant_total' => $infantTotal,
                'extra_total' => $extraTotal,
                'extra_bed_count' => $extraBedCount,
                'base_adult_count' => $baseAdultCount,
                'total_price' => $totalPrice,
            ],
            'nightly_prices' => $nightlyPrices,
        ];
    }

    private function policyRate(
        ?int $roomRate,
        int $capacity,
        string $pricingType,
        ?int $pricingValue
    ): ?int {
        if ($roomRate === null || $capacity <= 0) {
            return null;
        }

        $nominalPersonRate = $roomRate / $capacity;

        return match ($pricingType) {
            'adult' => (int) round($nominalPersonRate),
            'free' => 0,
            'half' => (int) round($nominalPersonRate / 2),
            'percent' => $pricingValue === null
                ? null
                : (int) round($nominalPersonRate * $pricingValue / 100),
            'fixed' => $pricingValue,
            default => null,
        };
    }
}
