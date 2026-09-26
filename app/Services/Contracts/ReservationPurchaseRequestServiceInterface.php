<?php

namespace App\Services\Contracts;

use App\Models\Reservation;

interface ReservationPurchaseRequestServiceInterface
{
    public function request(string $reservationNumber): Reservation;
}
