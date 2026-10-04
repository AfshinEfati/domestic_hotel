<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Http\SnappTripClient;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper\SnappTripMapper;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use RuntimeException;

final class SnappTripGatewayFactory
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripMapper $mapper,
    ) {
    }

    public function make(Provider $provider): SnappTripGateway
    {
        $apiKey = trim((string) data_get(SnappTripSettings::from($provider), 'api_key', ''));
        if ($apiKey === '' || $apiKey === SnappTripSettings::API_KEY_PLACEHOLDER) {
            throw new RuntimeException('SnappTrip real api_key must be configured in the provider database settings before outbound use.');
        }

        return new SnappTripGateway(
            new SnappTripClient($provider, $this->outboundGuard),
            $this->mapper,
        );
    }
}
