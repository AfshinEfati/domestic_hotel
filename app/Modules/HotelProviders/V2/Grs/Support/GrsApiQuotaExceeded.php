<?php

namespace App\Modules\HotelProviders\V2\Grs\Support;

use RuntimeException;

class GrsApiQuotaExceeded extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('GRS API quota exhausted; request postponed.', 429);
    }
}
