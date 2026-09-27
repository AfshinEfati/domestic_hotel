<?php

namespace App\Services\Contracts;

use App\Models\Reservation;

interface ManualReservationPurchaseServiceInterface
{
    public function show(int $reservationId): Reservation;

    public function update(int $reservationId, array $payload): Reservation;

    public function addHotel(int $reservationId, array $payload): Reservation;

    public function selectHotel(int $reservationId, int $reservationHotelId, string $accCode): Reservation;

    public function addRoom(int $reservationId, array $payload): Reservation;

    public function selectRoom(int $reservationId, int $reservationRoomId, string $accCode): Reservation;

    public function assignGuests(int $reservationId, array $payload): Reservation;

    public function updatePurchase(int $reservationId, int $purchaseId, array $payload): Reservation;

    public function addPayment(int $reservationId, int $purchaseId, array $payload): Reservation;

    public function confirm(int $reservationId, array $payload): Reservation;

    public function reject(int $reservationId, array $payload): Reservation;
}
