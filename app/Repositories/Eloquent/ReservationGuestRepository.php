<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationGuest;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;

class ReservationGuestRepository extends BaseRepository implements ReservationGuestRepositoryInterface
{
    public function __construct(ReservationGuest $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ReservationGuest
    {
        /** @var ReservationGuest|null */
        return parent::find($id);
    }

    public function store(array $data): ReservationGuest
    {
        /** @var ReservationGuest */
        return parent::store($data);
    }
}
