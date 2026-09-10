<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationHotel;

interface ReservationHotelRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationHotel;

    public function store(array $data): ReservationHotel;

    public function findForReservation(int $reservationId, int $reservationHotelId): ?ReservationHotel;

    public function findByReservationAndAccommodation(int $reservationId, int $accommodationId): ?ReservationHotel;

    public function clearFinalByReservation(int $reservationId): void;

    public function markFinal(int $reservationHotelId): bool;
}
