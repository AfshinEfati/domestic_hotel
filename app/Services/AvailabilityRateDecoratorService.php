<?php

namespace App\Services;

use App\Services\Contracts\AvailabilityRateDecoratorServiceInterface;
use App\Services\Contracts\HotelRatePricingServiceInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;

class AvailabilityRateDecoratorService implements AvailabilityRateDecoratorServiceInterface
{
    public function __construct(
        private readonly RoomCalendarServiceInterface $roomCalendarService,
        private readonly HotelRatePricingServiceInterface $ratePricingService,
    ) {}

    public function decorate(iterable $accommodations): iterable
    {
        $roomTypeIds = [];
        $days = [];

        foreach ($accommodations as $accommodation) {
            foreach ($accommodation->getAttribute('available_rooms') ?? [] as $room) {
                $roomTypeIds[] = (int) $room->id;

                foreach ($room->getAttribute('nightly_prices') ?? [] as $night) {
                    if (!empty($night['date'])) {
                        $days[] = $night['date'];
                    }
                }
            }
        }

        $calendars = $this->roomCalendarService->getByRoomTypeIdsAndDays(
            array_values(array_unique($roomTypeIds)),
            array_values(array_unique($days))
        );

        // The selected calendar is identified by its primary key.
        $calendarMap = $calendars->keyBy('id');

        foreach ($accommodations as $accommodation) {
            foreach ($accommodation->getAttribute('available_rooms') ?? [] as $room) {
                $nightlyPrices = $room->getAttribute('nightly_prices') ?? [];

                foreach ($nightlyPrices as $index => $night) {
                    $calendarId = $night['calendar_id'] ?? null;

                    $calendar = $calendarId !== null
                        ? $calendarMap->get($calendarId)
                        : null;

                    $providerId = $calendar?->provider_id
                        ?? $room->getAttribute('provider_id');

                    foreach (['adult', 'child', 'infant'] as $type) {
                        if (
                            !isset($night[$type])
                            || !is_array($night[$type])
                        ) {
                            continue;
                        }

                        $night[$type]['final_rate'] =
                            $this->ratePricingService->calculateFinalRate(
                                $night[$type]['grs_rate'] ?? null,
                                $providerId !== null
                                    ? (int) $providerId
                                    : null
                            );
                    }

                    $extraGrsRate = $calendar?->extend_bed_grs_rate;

                    if (
                        $extraGrsRate === null
                        && (int) $room->extra_capacity > 0
                        && $calendar?->grs_rate !== null
                        && (int) $room->capacity > 0
                    ) {
                        $extraGrsRate = $calendar->grs_rate / $room->capacity;
                    }

                    $night['extra_grs_rate'] = $extraGrsRate;

                    $night['extra_final_rate'] =
                        $this->ratePricingService->calculateFinalRate(
                            $extraGrsRate,
                            $providerId !== null
                                ? (int) $providerId
                                : null
                        );

                    $nightlyPrices[$index] = $night;
                }

                $room->setAttribute('nightly_prices', $nightlyPrices);
            }
        }

        return $accommodations;
    }
}
