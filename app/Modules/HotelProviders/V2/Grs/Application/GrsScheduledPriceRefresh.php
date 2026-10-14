<?php

namespace App\Modules\HotelProviders\V2\Grs\Application;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\RefreshGrsPropertyPricesJob;
use App\Modules\HotelProviders\V2\Grs\Support\GrsApiQuota;
use App\Modules\HotelProviders\V2\Grs\Support\GrsSettings;

/** GRS provider policy; shared coordination owns hotel selection. */
final class GrsScheduledPriceRefresh implements PriceRefreshSchedulerHandler
{
    public function enabled(Provider $provider): bool
    {
        return $provider->code === GrsSettings::PROVIDER_CODE && $provider->is_active;
    }

    public function hotelCapacityPerMinute(Provider $provider, ?int $days = null): int
    {
        $windowMinutes = max(1, GrsApiQuota::windowMinutes($provider));

        return max(1, intdiv(GrsApiQuota::maxRequests($provider), $windowMinutes));
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
            $provider->code !== GrsSettings::PROVIDER_CODE
            || !$provider->is_active
            || (int) $map->provider_id !== (int) $provider->id
            || (int) $map->accommodation_id !== $accommodationId
            || $map->is_disabled
            || trim((string) $map->provider_property_id) === ''
        ) {
            return false;
        }

        $days ??= GrsSettings::from($provider)['default_days'];

        RefreshGrsPropertyPricesJob::dispatch(
            $scheduleId,
            $accommodationId,
            (int) $provider->id,
            $days,
            $refreshStateId,
            $cycleKey,
        )->onQueue('grs-prices');

        return true;
    }
}
