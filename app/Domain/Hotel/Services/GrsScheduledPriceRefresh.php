<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Jobs\Hotel\V2\SyncGrsDuePricesJob;
use App\Models\Provider;

/** Only GRS settings and its own job are handled here; no queries or HTTP calls. */
class GrsScheduledPriceRefresh implements PriceRefreshSchedulerHandler
{
    public function dispatchIfEnabled(Provider $provider): bool
    {
        if ($provider->code !== 'grs' || !$provider->is_active || !$provider->is_online ||
            !GrsRefreshSettings::from($provider)['scheduler_enabled']) {
            return false;
        }

        SyncGrsDuePricesJob::dispatch();
        return true;
    }
}
