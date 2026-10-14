<?php

namespace App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository as LegacyGrsHotelDetailsRepository;

/**
 * Module-owned compatibility implementation.
 *
 * The legacy repository remains available until the GRS migration is fully
 * verified. Runtime resolution is redirected here with identical behaviour.
 */
class GrsHotelDetailsRepository extends LegacyGrsHotelDetailsRepository
{
}
