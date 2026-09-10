<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationRoom;

interface ReservationRoomRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationRoom;

    public function store(array $data): ReservationRoom;

    public function findForReservationHotel(int $reservationHotelId, int $reservationRoomId): ?ReservationRoom;
}
