<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Exceptions;

use RuntimeException;

final class SnappTripRateLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct("SnappTrip API quota is exhausted; retry after {$retryAfterSeconds} second(s).");
    }
}
