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

                foreach ($room->getAttribute('nightly_prices') ?? [] as $nightlyPrice) {
                    if (!empty($nightlyPrice['date'])) {
                        $days[] = $nightlyPrice['date'];
                    }
                }
            }
        }

        $calendars = $this->roomCalendarService->getByRoomTypeIdsAndDays(
            array_values(array_unique($roomTypeIds)),
            array_values(array_unique($days)),
        );

        $calendarMap = [];

        foreach ($calendars as $calendar) {
            $key = $calendar->room_type_id . '|' . $calendar->day?->format('Y-m-d');
            $calendarMap[$key] ??= $calendar;
        }

        foreach ($accommodations as $accommodation) {
            foreach ($accommodation->getAttribute('available_rooms') ?? [] as $room) {
                $nightlyPrices = $room->getAttribute('nightly_prices') ?? [];

                foreach ($nightlyPrices as $index => $nightlyPrice) {
                    $day = $nightlyPrice['date'] ?? null;
                    $calendar = $day !== null
                        ? ($calendarMap[$room->id . '|' . $day] ?? null)
                        : null;

                    $providerId = $calendar?->provider_id;

                    if (isset($nightlyPrice['adult']) && is_array($nightlyPrice['adult'])) {
                        $nightlyPrice['adult']['final_rate'] = $this->ratePricingService->calculateFinalRate(
                            $nightlyPrice['adult']['grs_rate'] ?? null,
                            $providerId
                        );
                    }

                    if (isset($nightlyPrice['child']) && is_array($nightlyPrice['child'])) {
                        $nightlyPrice['child']['final_rate'] = $this->ratePricingService->calculateFinalRate(
                            $nightlyPrice['child']['grs_rate'] ?? null,
                            $providerId
                        );
                    }

                    if (isset($nightlyPrice['infant']) && is_array($nightlyPrice['infant'])) {
                        $nightlyPrice['infant']['final_rate'] = $this->ratePricingService->calculateFinalRate(
                            $nightlyPrice['infant']['grs_rate'] ?? null,
                            $providerId
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

                    $nightlyPrice['extra_grs_rate'] = $extraGrsRate;
                    $nightlyPrice['extra_final_rate'] = $this->ratePricingService->calculateFinalRate(
                        $extraGrsRate,
                        $providerId
                    );

                    $nightlyPrices[$index] = $nightlyPrice;
                }

                $room->setAttribute('nightly_prices', $nightlyPrices);
            }
        }

        return $accommodations;
    }
}
