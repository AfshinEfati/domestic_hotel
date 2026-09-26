<?php

namespace App\Services\Contracts;

use App\Models\Reservation;

interface ReservationPurchaseRequestServiceInterface
{
    public function request(int $reservationId): Reservation;
}
