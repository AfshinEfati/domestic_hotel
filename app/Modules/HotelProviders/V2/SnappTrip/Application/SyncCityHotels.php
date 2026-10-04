<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncHotelDetailsJob;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;

final class SyncCityHotels
{
    private const PAGE_SIZE = 100;
    private const DETAILS_BATCH_SIZE = 10;

    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
    ) {
    }

    /** @return array{hotels:int,detail_jobs:int} */
    public function execute(string $providerCityId): array
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $gateway = $this->gateways->make($provider);
        $offset = 0;
        $hotels = [];

        do {
            $batch = $gateway->cityHotels($providerCityId, self::PAGE_SIZE, $offset);
            foreach ($batch as $hotel) {
                $propertyId = trim((string) ($hotel['provider_property_id'] ?? ''));
                if ($propertyId === '') {
                    continue;
                }
                $hotels[$propertyId] = $hotel;
            }
            $offset += $batch->count();
        } while ($batch->count() === self::PAGE_SIZE);

        $jobs = 0;
        foreach (array_chunk($hotels, self::DETAILS_BATCH_SIZE, true) as $chunk) {
            SyncHotelDetailsJob::dispatch(
                array_keys($chunk),
                array_map(static fn (array $hotel): ?string => $hotel['provider_url'] ?? null, $chunk),
            )->onQueue('snapptrip-static');
            $jobs++;
        }

        return ['hotels' => count($hotels), 'detail_jobs' => $jobs];
    }
}
