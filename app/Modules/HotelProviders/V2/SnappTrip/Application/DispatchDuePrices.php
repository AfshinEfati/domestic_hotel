<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\RefreshAvailabilityJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripApiQuota;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;

/** Provider-specific manual due scan; scheduled refresh is hotel-centric in the shared scheduler. */
final class DispatchDuePrices
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly HotelPriceRefreshScheduleRepository $schedules,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
    ) {
    }

    public function execute(?int $days = null): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        if (SnappTripApiQuota::cooldownSeconds((int) $provider->id) > 0) {
            return 0;
        }

        $this->schedules->assertReady();
        $settings = SnappTripSettings::from($provider);
        $maxRequests = (int) data_get($settings, 'rate_limit.max_requests', 120);
        $windowMinutes = max(1, (int) data_get($settings, 'rate_limit.window_minutes', 1));
        $hotelCapacity = max(1, intdiv($maxRequests, $windowMinutes * 2));
        $days ??= (int) data_get($settings, 'price_refresh.default_days', 90);
        $count = 0;

        foreach ($this->schedules->due($hotelCapacity) as $schedule) {
            $accommodationId = (int) $schedule->gds_id;
            if ($accommodationId <= 0) {
                continue;
            }

            $map = $this->maps->findForAccommodationAndProvider($accommodationId, (int) $provider->id);
            if (
                $map === null
                || $map->is_disabled
                || trim((string) $map->provider_property_id) === ''
            ) {
                continue;
            }

            RefreshAvailabilityJob::dispatch(
                (int) $schedule->id,
                $accommodationId,
                (int) $provider->id,
                $days,
            )->onQueue('snapptrip-prices');
            $count++;
        }

        return $count;
    }
}
