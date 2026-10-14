<?php

namespace App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence;

use App\Domain\Hotel\Repositories\GrsAvailabilityPersistenceRepository as LegacyGrsAvailabilityPersistenceRepository;

/**
 * Module-owned compatibility implementation.
 *
 * The legacy repository remains in place during migration. Runtime resolution is
 * redirected here so repository ownership can move without changing behaviour.
 */
class GrsAvailabilityPersistenceRepository extends LegacyGrsAvailabilityPersistenceRepository
{
}
