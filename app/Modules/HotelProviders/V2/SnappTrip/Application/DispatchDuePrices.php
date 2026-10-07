<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

/** Manual SnappTrip trigger using the same coordinated provider-state flow as the scheduler. */
final class DispatchDuePrices
{
    public function __construct(
        private readonly ProviderPriceRefreshScheduler $scheduler,
    ) {
    }

    public function execute(?int $days = null): int
    {
        return $this->scheduler->dispatch(SnappTripSettings::PROVIDER_CODE, $days);
    }
}
