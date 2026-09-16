<?php

namespace App\Repositories\Contracts;

use App\Models\ReservationGuest;

interface ReservationGuestRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ReservationGuest;

    public function store(array $data): ReservationGuest;
}
