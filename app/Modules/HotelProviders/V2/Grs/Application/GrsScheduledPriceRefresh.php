<?php

namespace App\Modules\HotelProviders\V2\Grs\Application;

use App\Domain\Hotel\Services\GrsScheduledPriceRefresh as LegacyGrsScheduledPriceRefresh;

/**
 * Transitional module-owned scheduler policy.
 *
 * Behaviour is inherited from the current implementation until the GRS module
 * parity suite proves the new boundary. The legacy class remains untouched.
 */
final class GrsScheduledPriceRefresh extends LegacyGrsScheduledPriceRefresh
{
}
