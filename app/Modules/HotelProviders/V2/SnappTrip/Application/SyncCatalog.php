<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncCityHotelsJob;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class SyncCatalog
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripCatalogRepository $catalog,
    ) {
    }

    /** @return array{cities:int,city_jobs:int} */
    public function execute(): array
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $cities = $this->gateways->make($provider)->cities();
        $persisted = $this->catalog->persistCities($provider, $cities);

        foreach ($cities as $city) {
            SyncCityHotelsJob::dispatch((string) $city['id'])->onQueue('snapptrip-static');
        }

        return ['cities' => $persisted, 'city_jobs' => $cities->count()];
    }
}
