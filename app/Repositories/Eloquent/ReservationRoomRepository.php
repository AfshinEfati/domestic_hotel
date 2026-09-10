<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationRoom;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;

class ReservationRoomRepository extends BaseRepository implements ReservationRoomRepositoryInterface
{
    public function __construct(ReservationRoom $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ReservationRoom
    {
        /** @var ReservationRoom|null */
        return parent::find($id);
    }

    public function store(array $data): ReservationRoom
    {
        /** @var ReservationRoom */
        return parent::store($data);
    }

    public function findForReservationHotel(int $reservationHotelId, int $reservationRoomId): ?ReservationRoom
    {
        return $this->model
            ->newQuery()
            ->where('reservation_hotel_id', $reservationHotelId)
            ->whereKey($reservationRoomId)
            ->first();
    }
}
