<?php

namespace App\Jobs\Hotel;

use App\Modules\HotelProviders\V2\Grs\Application\Jobs\SyncGrsAvailabilityForPropertyJob as ModuleSyncGrsAvailabilityForPropertyJob;

/**
 * Temporary queue-compatibility alias for serialized jobs dispatched before the
 * GRS module migration. New code must use the module-owned job directly.
 */
class SyncGrsAvailabilityForPropertyJob extends ModuleSyncGrsAvailabilityForPropertyJob
{
}
