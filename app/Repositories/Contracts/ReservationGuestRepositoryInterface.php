<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationGuest;

interface ReservationGuestRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationGuest;

    public function store(array $data): ReservationGuest;

    public function findForReservation(int $reservationId, int $guestId): ?ReservationGuest;

    /** @return array<int> */
    public function getIdsForReservation(int $reservationId): array;

    public function assignToRoom(int $guestId, int $reservationRoomId): bool;
}
