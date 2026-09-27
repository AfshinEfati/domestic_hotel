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

    public function findByHotelNumberAndType(
        int $reservationHotelId,
        int $roomNumber,
        int $type
    ): ?ReservationRoom {
        return $this->model
            ->newQuery()
            ->where('reservation_hotel_id', $reservationHotelId)
            ->where('room_number', $roomNumber)
            ->where('type', $type)
            ->first();
    }

    public function clearFinalByHotelAndNumber(int $reservationHotelId, int $roomNumber): void
    {
        $this->model
            ->newQuery()
            ->where('reservation_hotel_id', $reservationHotelId)
            ->where('room_number', $roomNumber)
            ->where('is_final', true)
            ->update(['is_final' => false]);
    }

    public function getFinalByHotel(int $reservationHotelId): iterable
    {
        return $this->model
            ->newQuery()
            ->with(['guests'])
            ->where('reservation_hotel_id', $reservationHotelId)
            ->where('is_final', true)
            ->orderBy('room_number')
            ->get();
    }
}
