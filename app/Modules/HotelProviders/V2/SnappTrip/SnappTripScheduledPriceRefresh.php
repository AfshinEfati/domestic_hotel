<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncDuePricesJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class SnappTripScheduledPriceRefresh implements PriceRefreshSchedulerHandler
{
    public function dispatchIfEnabled(Provider $provider): bool
    {
        if (
            $provider->code !== SnappTripSettings::PROVIDER_CODE
            || !$provider->is_active
            || !$provider->is_online
            || data_get(SnappTripSettings::from($provider), 'price_refresh.scheduler_enabled') !== true
        ) {
            return false;
        }

        SyncDuePricesJob::dispatch()->onQueue('snapptrip-prices');

        return true;
    }
}
