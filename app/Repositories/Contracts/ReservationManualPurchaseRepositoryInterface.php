<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationManualPurchase;

interface ReservationManualPurchaseRepositoryInterface extends BaseRepositoryInterface
{
    public function firstOrCreateForPurchase(int $reservationPurchaseId): ReservationManualPurchase;
}
