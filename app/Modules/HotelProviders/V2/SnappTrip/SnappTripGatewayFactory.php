<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Http\SnappTripClient;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper\SnappTripMapper;

final class SnappTripGatewayFactory
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripMapper $mapper,
    ) {
    }

    public function make(Provider $provider): SnappTripGateway
    {
        return new SnappTripGateway(
            new SnappTripClient($provider, $this->outboundGuard),
            $this->mapper,
        );
    }
}
