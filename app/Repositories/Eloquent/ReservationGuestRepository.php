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

    public function findForReservation(int $reservationId, int $guestId): ?ReservationGuest
    {
        return $this->model
            ->newQuery()
            ->whereKey($guestId)
            ->whereHas(
                'room.reservationHotel',
                fn ($query) => $query->where('reservation_id', $reservationId)
            )
            ->first();
    }

    public function getIdsForReservation(int $reservationId): array
    {
        return $this->model
            ->newQuery()
            ->whereHas(
                'room.reservationHotel',
                fn ($query) => $query->where('reservation_id', $reservationId)
            )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function assignToRoom(int $guestId, int $reservationRoomId): bool
    {
        return $this->model
            ->newQuery()
            ->whereKey($guestId)
            ->update(['reservation_room_id' => $reservationRoomId]) === 1;
    }
}
