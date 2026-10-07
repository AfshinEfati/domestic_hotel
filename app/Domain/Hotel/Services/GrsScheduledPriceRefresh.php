<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\V2\GrsApiQuota;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;

/** GRS provider policy; shared coordination owns hotel selection. */
class GrsScheduledPriceRefresh implements PriceRefreshSchedulerHandler
{
    public function enabled(Provider $provider): bool
    {
        return $provider->code === 'grs'
            && $provider->is_active
            && GrsRefreshSettings::from($provider)['scheduler_enabled'];
    }

    public function hotelCapacityPerMinute(Provider $provider): int
    {
        $windowMinutes = max(1, GrsApiQuota::windowMinutes($provider));

        return max(1, intdiv(GrsApiQuota::maxRequests($provider), $windowMinutes));
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $refreshStateId,
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

        $days ??= GrsRefreshSettings::from($provider)['default_days'];

        RefreshGrsPropertyPricesJob::dispatch(
            $scheduleId,
            $accommodationId,
            (int) $provider->id,
            $days,
            $refreshStateId,
        )->onQueue('grs-prices');

        return true;
    }
}
