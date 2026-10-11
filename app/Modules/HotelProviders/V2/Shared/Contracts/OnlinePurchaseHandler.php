<?php

namespace App\Modules\HotelProviders\V2\Shared\Contracts;

use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;

interface OnlinePurchaseHandler
{
    /**
     * Rooms with the same key can be fulfilled by a single provider booking.
     */
    public function groupingKey(ReservationRoom $room): string;

    public function queue(): string;

    public function execute(
        ReservationPurchase $purchase,
        int $attempt,
    ): OnlinePurchaseExecutionResult;

    /**
     * Provider-specific fields that must be reset before an explicit operator retry.
     *
     * @return array<string,mixed>
     */
    public function prepareRetry(ReservationPurchase $purchase): array;
}
