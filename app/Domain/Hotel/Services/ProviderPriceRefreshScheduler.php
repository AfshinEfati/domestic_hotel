<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use RuntimeException;

/**
 * Hotel-centric refresh scheduler.
 *
 * A hotel is selected once from the shared SSP schedule, then every active mapped
 * provider receives its own refresh job. Provider jobs persist their own calendar
 * rows, so RoomCalendar remains a combined cache of every provider offer.
 */
class ProviderPriceRefreshScheduler
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providers,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly HotelProviderRegistry $registry,
    ) {
    }

    public function dispatch(): int
    {
        $this->schedules->assertReady();

        $enabled = [];
        $hotelCapacity = null;

        foreach ($this->providers->getAll() as $provider) {
            $handlerClass = $this->registry->priceRefreshHandler((string) $provider->code);
            if ($handlerClass === null) {
                continue;
            }

            $handler = app($handlerClass);
            if (!$handler instanceof PriceRefreshSchedulerHandler) {
                throw new RuntimeException("Invalid price refresh scheduler handler for {$provider->code}.");
            }
            if (!$handler->enabled($provider)) {
                continue;
            }

            $capacity = max(1, $handler->hotelCapacityPerMinute($provider));
            $hotelCapacity = $hotelCapacity === null ? $capacity : min($hotelCapacity, $capacity);
            $enabled[(int) $provider->id] = [$provider, $handler];
        }

        if ($enabled === [] || $hotelCapacity === null) {
            return 0;
        }

        $dispatched = 0;

        foreach ($this->schedules->due($hotelCapacity) as $schedule) {
            $accommodationId = (int) $schedule->gds_id;
            if ($accommodationId <= 0) {
                $this->schedules->markMappingIssueHandled((int) $schedule->id, $accommodationId);
                continue;
            }

            $hotelDispatched = 0;
            foreach ($this->maps->activeForAccommodation($accommodationId) as $map) {
                $entry = $enabled[(int) $map->provider_id] ?? null;
                if ($entry === null) {
                    continue;
                }

                [$provider, $handler] = $entry;
                if ($handler->dispatch(
                    $provider,
                    $map,
                    (int) $schedule->id,
                    $accommodationId,
                )) {
                    $hotelDispatched++;
                    $dispatched++;
                }
            }

            // A due hotel with no usable provider mapping must not be selected every
            // minute forever. This advances only the shared refresh due time and does
            // not delete mappings or previously persisted provider calendar rows.
            if ($hotelDispatched === 0) {
                $this->schedules->markMappingIssueHandled((int) $schedule->id, $accommodationId);
            }
        }

        return $dispatched;
    }
}
