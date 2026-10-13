<?php

namespace App\Modules\HotelProviders\V2\Shared\Contracts;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use Illuminate\Support\Collection;

interface OnlinePurchasePreflightHandler
{
    /**
     * Validate the whole reservation/provider purchase set before any provider booking is created.
     *
     * @param Collection<int, ReservationPurchase> $purchases
     */
    public function check(Collection $purchases): OnlinePurchasePreflightResult;
}
