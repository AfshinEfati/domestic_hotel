<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationRepository extends BaseRepository implements ReservationRepositoryInterface
{
    public function __construct(Reservation $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?Reservation
    {
        /** @var Reservation|null */
        return parent::find($id);
    }

    public function store(array $data): Reservation
    {
        /** @var Reservation */
        return parent::store($data);
    }

    public function findByReservationNumber(string $reservationNumber): ?Reservation
    {
        return $this->model
            ->newQuery()
            ->with([
                'hotels.accommodation',
                'hotels.rooms.roomType',
                'hotels.rooms.ratePlan',
                'hotels.rooms.purchaseSegments.provider',
            ])
            ->where('reservation_number', $reservationNumber)
            ->first();
    }

    public function findForUpdate(int $id): ?Reservation
    {
        return $this->model
            ->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }
}
