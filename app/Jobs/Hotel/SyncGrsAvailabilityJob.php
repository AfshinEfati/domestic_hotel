<?php

namespace App\Jobs\Hotel;

use App\Modules\HotelProviders\V2\Grs\Application\Jobs\SyncGrsAvailabilityJob as ModuleSyncGrsAvailabilityJob;

/**
 * Temporary queue-compatibility alias for serialized jobs dispatched before the
 * GRS module migration. New code must use the module-owned job directly.
 */
class SyncGrsAvailabilityJob extends ModuleSyncGrsAvailabilityJob
{
}
