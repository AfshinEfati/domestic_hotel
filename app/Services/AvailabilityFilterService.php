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
                    ->where(
                        'en_name',
                        'like',
                        '%' . strtolower($city) . '%'
                    )
                    ->orWhere(
                        'fa_name',
                        'like',
                        '%' . $city . '%'
                    );
            })
            ->with([
                'childPolicy',
                'rooms.roomTypeName',
                'rules',
            ])
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
            $hotelCalendars = $calendars->get(
                $accommodation->id,
                collect()
            );

            $rooms = $this->findCheapestAvailableCombination(
                $accommodation,
                $data['rooms'],
                $dates,
                $hotelCalendars
            );

            // A hotel must satisfy the entire requested room combination.
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
        $policy = $accommodation->childPolicy;

        // Inactive child policies are not applied.
        if ($policy !== null && !$policy->status) {
            $policy = null;
        }

        $groups = [];

        foreach ($requestedRooms as $requestIndex => $requestedRoom) {
            $passengers = $this->normalizePassengers(
                $requestedRoom['passengers'] ?? [],
                $policy
            );

            if ($passengers === []) {
                return collect();
            }

            $counts = [
                'adult' => 0,
                'child' => 0,
                'infant' => 0,
            ];

            foreach ($passengers as $passenger) {
                $counts[$passenger['type']]++;
            }

            // Equal compositions need the same room option and sufficient
            // inventory for the entire group.
            $signature = implode(':', [
                $counts['adult'],
                $counts['child'],
                $counts['infant'],
            ]);

            if (!isset($groups[$signature])) {
                $groups[$signature] = [
                    'counts' => $counts,
                    'indexes' => [],
                    'quantity' => 0,
                    'options' => [],
                ];
            }

            $groups[$signature]['indexes'][] = $requestIndex;
            $groups[$signature]['quantity']++;
        }

        $calendarsByRoom = $hotelCalendars->groupBy('room_type_id');
        $groups = array_values($groups);

        foreach ($groups as &$group) {
            foreach ($accommodation->rooms as $room) {
                if ($room->out_of_service || $room->capacity <= 0) {
                    continue;
                }

                $roomCalendars = $calendarsByRoom->get(
                    $room->id,
                    collect()
                );

                // Do not mix providers or rate plans across nights.
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
                            || $calendar->inventory < $group['quantity']
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
                        $group['counts'],
                        $policy,
                        $dates,
                        $byDay
                    );

                    if ($priceData === null) {
                        continue;
                    }

                    $firstCalendar = $byDay->get($dates[0]);

                    $group['options'][] = [
                        'room' => $room,
                        'price' => $priceData,
                        'provider_id' => (int) $firstCalendar->provider_id,
                        'rate_plan' => $firstCalendar->ratePlan,
                        'inventory_by_day' => $inventoryByDay,
                        'min_inventory' => min($inventoryByDay),
                    ];
                }
            }

            if ($group['options'] === []) {
                return collect();
            }

            usort(
                $group['options'],
                static function (array $a, array $b): int {
                    return (
                        $a['price']['total_price']
                        <=> $b['price']['total_price']
                    ) ?: (
                        $a['room']->id <=> $b['room']->id
                    ) ?: (
                        $a['provider_id'] <=> $b['provider_id']
                    );
                }
            );
        }

        unset($group);

        // Search the most constrained room group first.
        usort(
            $groups,
            static fn (array $a, array $b): int =>
                count($a['options']) <=> count($b['options'])
        );

        // Minimum remaining cost, used to prune expensive combinations.
        $minimumRemaining = array_fill(
            0,
            count($groups) + 1,
            0
        );

        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $minimumRemaining[$i] =
                $minimumRemaining[$i + 1]
                + (
                    $groups[$i]['options'][0]['price']['total_price']
                    * $groups[$i]['quantity']
                );
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
            $groups,
            $dates,
            $minimumRemaining
        ): void {
            if (
                $cost + $minimumRemaining[$depth]
                >= $bestCost
            ) {
                return;
            }

            if ($depth === count($groups)) {
                $bestCost = $cost;
                $bestSelection = $selection;
                return;
            }

            $group = $groups[$depth];
            $quantity = $group['quantity'];

            foreach ($group['options'] as $option) {
                $roomId = (int) $option['room']->id;

                $newUsed = ($used[$roomId] ?? 0) + $quantity;
                $newLimits = $limits;
                $valid = true;

                foreach ($dates as $day) {
                    // Different offers of one room type must not
                    // independently spend the same physical inventory.
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
                $newSelection[$depth] = $option;

                $newCost = $cost
                    + $option['price']['total_price'] * $quantity;

                $search(
                    $depth + 1,
                    $newCost,
                    $newUsedByRoom,
                    $newLimits,
                    $newSelection
                );
            }
        };

        $search(0, 0, [], [], []);

        if ($bestSelection === []) {
            return collect();
        }

        $selectedRooms = [];

        foreach ($groups as $groupIndex => $group) {
            $option = $bestSelection[$groupIndex];

            foreach ($group['indexes'] as $requestIndex) {
                // Clone: the same room type can appear in multiple
                // requested rooms without overwriting its attributes.
                $room = clone $option['room'];

                $room->setAttribute(
                    'pricing',
                    $option['price']['pricing']
                );

                $room->setAttribute(
                    'total_price',
                    $option['price']['total_price']
                );

                $room->setAttribute(
                    'nightly_prices',
                    $option['price']['nightly_prices']
                );

                $room->setAttribute(
                    'ratePlan',
                    $option['rate_plan']
                );

                $room->setAttribute(
                    'provider_id',
                    $option['provider_id']
                );

                $room->setAttribute(
                    'available_inventory',
                    $option['min_inventory']
                );

                $room->setAttribute(
                    'required_inventory',
                    $group['quantity']
                );

                $room->setAttribute(
                    'requested_room_index',
                    $requestIndex
                );

                $room->setAttribute(
                    'extra_bed_count',
                    $option['price']['extra_bed_count']
                );

                $selectedRooms[$requestIndex] = $room;
            }
        }

        ksort($selectedRooms);

        return collect(array_values($selectedRooms));
    }

    private function normalizePassengers(
        array $passengers,
        ?HotelChildPolicy $policy
    ): array {
        if ($passengers === []) {
            return [];
        }

        $normalized = [];
        $coveredChildren = 0;
        $coveredInfants = 0;

        foreach ($passengers as $passenger) {
            $inputType = match ($passenger['type'] ?? null) {
                'adl', 'adult' => 'adult',
                'chd', 'child' => 'child',
                'inf', 'infant' => 'infant',
                default => null,
            };

            if ($inputType === null) {
                return [];
            }

            // Explicit adults stay adults. Their age does not
            // accidentally turn them into infants.
            if ($inputType === 'adult') {
                $normalized[] = ['type' => 'adult'];
                continue;
            }

            // Child/infant ages are validated by AvailabilityRequest.
            if (!isset($passenger['age'])) {
                return [];
            }

            $age = (int) $passenger['age'];

            if ($policy === null) {
                $normalized[] = ['type' => 'adult'];
                continue;
            }

            $isInfantAge =
                $policy->max_infant_age > 0
                && $age < $policy->max_infant_age;

            if ($isInfantAge) {
                $infantLimit = $policy->max_infants_covered;

                if (
                    $infantLimit === null
                    || $coveredInfants < $infantLimit
                ) {
                    $normalized[] = ['type' => 'infant'];
                    $coveredInfants++;
                    continue;
                }

                if ($policy->infant_when_disabled === 'as_adult') {
                    $normalized[] = ['type' => 'adult'];
                    continue;
                }

                // Otherwise the infant is evaluated as a child.
            }

            $isChildAge =
                $policy->max_child_age > 0
                && $age <= $policy->max_child_age;

            if ($isChildAge) {
                $childLimit = $policy->max_children_covered;

                if (
                    $childLimit === null
                    || $coveredChildren < $childLimit
                ) {
                    $normalized[] = ['type' => 'child'];
                    $coveredChildren++;
                    continue;
                }
            }

            $normalized[] = ['type' => 'adult'];
        }

        return $normalized;
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

        // A child/infant without service does not occupy a bed.
        $childNeedsBed =
            $policy !== null
            && $policy->child_service_condition === 'with_service';

        $infantNeedsBed =
            $policy !== null
            && $policy->infant_service_condition === 'with_service';

        $requiredBeds = $adultCount
            + ($childNeedsBed ? $childCount : 0)
            + ($infantNeedsBed ? $infantCount : 0);

        if ($requiredBeds > $capacity + $extraCapacity) {
            return null;
        }

        $extraBedCount = max(0, $requiredBeds - $capacity);

        // Adults use the base beds first.
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
            /** @var RoomCalendar|null $calendar */
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

            // A required but unpriceable child/infant makes this
            // option invalid instead of silently charging zero.
            if (
                ($childCount > 0 && $childDaily === null)
                || ($infantCount > 0 && $infantDaily === null)
            ) {
                return null;
            }

            $childDaily ??= 0;
            $infantDaily ??= 0;

            // Preserve the previous fallback: if an extra bed has no
            // separate daily rate, use one nominal capacity share.
            $extraUnit = $extraCapacity > 0
                ? (int) (
                    $calendar->extend_bed_daily_rate
                    ?? round($basePrice / $capacity)
                )
                : 0;

            $nightChildTotal = $childDaily * $childCount;
            $nightInfantTotal = $infantDaily * $infantCount;
            $nightExtraTotal = $extraUnit * $extraBedCount;

            $nightTotal = $basePrice
                + $nightChildTotal
                + $nightInfantTotal
                + $nightExtraTotal;

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

        $totalPrice = $roomBaseTotal
            + $childTotal
            + $infantTotal
            + $extraTotal;

        return [
            'total_price' => $totalPrice,
            'extra_bed_count' => $extraBedCount,

            'pricing' => [
                // Unit amounts over the entire stay.
                'adult' => $adultUnitTotal,
                'child' => $childUnitTotal,
                'infant' => $infantUnitTotal,
                'extra_price' => $extraUnitTotal,

                // Actual composition totals over the entire stay.
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
                : (int) round(
                    $nominalPersonRate * $pricingValue / 100
                ),

            'fixed' => $pricingValue,

            default => null,
        };
    }
}
