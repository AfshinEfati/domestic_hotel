<?php

namespace App\Modules\HotelProviders\V2\Grs\Infrastructure;

use App\Domain\Hotel\Providers\GRSAdapter as LegacyGrsAdapter;

/**
 * Transitional module adapter.
 *
 * It intentionally inherits the current GRS/Aghamat24 implementation unchanged so
 * registry ownership can move to the module before any provider behaviour is moved.
 * The legacy adapter stays in place until parity tests are complete.
 */
final class GrsAdapter extends LegacyGrsAdapter
{
}
