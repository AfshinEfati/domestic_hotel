<?php

namespace App\Services\Contracts;

use App\Models\Reservation;

interface OnlineReservationPurchaseRecoveryServiceInterface
{
    public function retry(int $reservationId, int $purchaseId): Reservation;

    public function convertToManual(int $reservationId, int $purchaseId): Reservation;
}
