<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

final readonly class SnappTripPurchaseBalancePreflightResult
{
    public function __construct(
        public int $balance,
        public int $requiredAmount,
    ) {
    }

    public function sufficient(): bool
    {
        return $this->balance >= $this->requiredAmount;
    }
}
