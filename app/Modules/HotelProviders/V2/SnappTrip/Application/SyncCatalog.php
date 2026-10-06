<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCityRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncCityHotelsJob;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class SyncCatalog
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripCityRepository $cities,
    ) {
    }

    /** @return array{cities:int,city_jobs:int} */
    public function execute(): array
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $providerCities = $this->gateways->make($provider)->cities();
        $persisted = $this->cities->persistMappedDomesticCities($provider, $providerCities);
        $mappedProviderCityIds = $this->cities->mappedProviderCityIds($provider, $providerCities);

        foreach ($mappedProviderCityIds as $providerCityId) {
            SyncCityHotelsJob::dispatch($providerCityId)->onQueue('snapptrip-static');
        }

        return ['cities' => $persisted, 'city_jobs' => count($mappedProviderCityIds)];
    }
}
