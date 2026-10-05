<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use InvalidArgumentException;

final class SyncHotelDetails
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripCatalogRepository $catalog,
    ) {
    }

    /**
     * @param string[] $providerPropertyIds
     * @param array<string,?string> $providerUrls
     * @return array{received:int,persisted:int}
     */
    public function execute(array $providerPropertyIds, array $providerUrls = []): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $providerPropertyIds,
        ))));
        if ($ids === [] || count($ids) > 10) {
            throw new InvalidArgumentException('SnappTrip hotel details sync accepts between 1 and 10 hotel IDs.');
        }

        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $gateway = $this->gateways->make($provider);
        $hotels = $gateway->hotels($ids);
        $facilities = $gateway->facilities($ids);
        $rooms = $gateway->rooms($ids);
        $persisted = 0;

        foreach ($hotels as $hotel) {
            $propertyId = trim((string) ($hotel['provider_property_id'] ?? ''));
            if ($propertyId === '') {
                continue;
            }

            $this->catalog->persistHotelBundle(
                $provider,
                $hotel,
                $facilities->get($propertyId, []),
                $rooms->get($propertyId, []),
                $providerUrls[$propertyId] ?? null,
            );
            $persisted++;
        }

        return ['received' => $hotels->count(), 'persisted' => $persisted];
    }
}
