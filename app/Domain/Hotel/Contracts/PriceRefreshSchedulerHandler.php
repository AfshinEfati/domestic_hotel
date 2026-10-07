<?php

namespace App\Domain\Hotel\Contracts;

use App\Models\AccommodationProviderMap;
use App\Models\Provider;

/** Provider-specific rate refresh policy; shared coordination owns hotel selection. */
interface PriceRefreshSchedulerHandler
{
    public function enabled(Provider $provider): bool;

    /** Maximum number of hotels this provider can safely start per scheduler minute. */
    public function hotelCapacityPerMinute(Provider $provider): int;

    /** Queue one provider refresh for one claimed local provider state. */
    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $refreshStateId,
        string $cycleKey,
        int $scheduleId,
        int $accommodationId,
        ?int $days = null,
    ): bool;
}
