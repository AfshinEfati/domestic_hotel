<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripPriceRefreshRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\RefreshAvailabilityJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripApiQuota;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class DispatchDuePrices
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripPriceRefreshRepository $refresh,
    ) {
    }

    public function execute(?int $days = null): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        if (SnappTripApiQuota::cooldownSeconds((int) $provider->id) > 0) {
            return 0;
        }

        $this->refresh->assertReady();
        $this->refresh->synchronizeStates($provider);

        $settings = SnappTripSettings::from($provider);
        $maxRequests = (int) data_get($settings, 'rate_limit.max_requests', 120);
        // A complete refresh intentionally checks domestic and foreign calendars.
        $propertyCapacity = max(1, intdiv($maxRequests, 2));
        $days ??= (int) data_get($settings, 'price_refresh.default_days', 90);
        $count = 0;

        foreach ($this->refresh->due($provider, $propertyCapacity) as $state) {
            RefreshAvailabilityJob::dispatch((int) $state->id, $days)->onQueue('snapptrip-prices');
            $count++;
        }

        return $count;
    }
}
