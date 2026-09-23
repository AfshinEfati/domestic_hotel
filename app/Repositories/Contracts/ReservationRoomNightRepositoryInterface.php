<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationRoomNight;

interface ReservationRoomNightRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationRoomNight;

    public function store(array $data): ReservationRoomNight;

    /** @return array<int, ReservationRoomNight> keyed by date (Y-m-d) */
    public function findForRoomKeyedByDate(int $reservationRoomId): array;
}
