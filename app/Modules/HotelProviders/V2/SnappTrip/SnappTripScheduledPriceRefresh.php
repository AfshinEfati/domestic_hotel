<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\RefreshAvailabilityJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class SnappTripScheduledPriceRefresh implements PriceRefreshSchedulerHandler
{
    public function enabled(Provider $provider): bool
    {
        return $provider->code === SnappTripSettings::PROVIDER_CODE
            && $provider->is_active
            && data_get(SnappTripSettings::from($provider), 'price_refresh.scheduler_enabled') === true;
    }

    public function hotelCapacityPerMinute(Provider $provider): int
    {
        $settings = SnappTripSettings::from($provider);
        $maxRequests = (int) data_get($settings, 'rate_limit.max_requests', 120);
        $windowMinutes = max(1, (int) data_get($settings, 'rate_limit.window_minutes', 1));

        // Each hotel refresh requests both domestic and foreign calendars.
        return max(1, intdiv($maxRequests, $windowMinutes * 2));
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $refreshStateId,
        string $cycleKey,
        int $scheduleId,
        int $accommodationId,
        ?int $days = null,
    ): bool {
        if (
            !$this->enabled($provider)
            || (int) $map->provider_id !== (int) $provider->id
            || (int) $map->accommodation_id !== $accommodationId
            || $map->is_disabled
            || trim((string) $map->provider_property_id) === ''
        ) {
            return false;
        }

        $days ??= (int) data_get(SnappTripSettings::from($provider), 'price_refresh.default_days', 90);

        RefreshAvailabilityJob::dispatch(
            $scheduleId,
            $accommodationId,
            (int) $provider->id,
            $days,
            $refreshStateId,
            $cycleKey,
        )->onQueue('snapptrip-prices');

        return true;
    }
}
