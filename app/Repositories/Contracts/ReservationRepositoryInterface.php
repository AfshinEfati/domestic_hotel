<?php

namespace App\Repositories\Contracts;

use App\Models\Reservation;

interface ReservationRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?Reservation;

    public function store(array $data): Reservation;

    public function findByReservationNumber(string $reservationNumber): ?Reservation;

    public function existsByReservationNumber(string $reservationNumber): bool;

    public function findForUpdate(int $id): ?Reservation;

    public function findByReservationNumberForPurchase(string $reservationNumber, bool $lockForUpdate = false): ?Reservation;
}
