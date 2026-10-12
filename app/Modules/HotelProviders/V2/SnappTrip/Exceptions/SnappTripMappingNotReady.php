<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Exceptions;

use RuntimeException;

final class SnappTripMappingNotReady extends RuntimeException
{
    /** @param string[] $providerRoomIds */
    public function __construct(
        public readonly string $providerPropertyId,
        public readonly array $providerRoomIds,
    ) {
        parent::__construct(sprintf(
            'SnappTrip static room mappings are still incomplete for hotel %s (rooms: %s).',
            $providerPropertyId,
            implode(', ', $providerRoomIds),
        ));
    }
}
