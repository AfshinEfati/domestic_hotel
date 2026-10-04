<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripOperationsRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use RuntimeException;

final class SyncBalance
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripOperationsRepository $operations,
    ) {
    }

    public function execute(): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $balance = $this->gateways->make($provider)->balance();
        if ($balance === null) {
            throw new RuntimeException('SnappTrip balance response did not contain a balance.');
        }

        $this->operations->saveBalance($provider, $balance);

        return $balance;
    }
}
