<?php

namespace App\Modules\HotelProviders\V2\Shared;

final readonly class OnlinePurchaseExecutionResult
{
    private function __construct(
        public bool $retry,
        public int $retryAfterSeconds,
    ) {
    }

    public static function done(): self
    {
        return new self(false, 0);
    }

    public static function retry(int $retryAfterSeconds = 15): self
    {
        return new self(true, max(1, $retryAfterSeconds));
    }
}
