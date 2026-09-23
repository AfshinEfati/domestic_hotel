<?php

namespace App\Repositories\Eloquent;

use App\Models\ReservationRoomNight;
use App\Repositories\Contracts\ReservationRoomNightRepositoryInterface;

class ReservationRoomNightRepository extends BaseRepository implements ReservationRoomNightRepositoryInterface
{
    public function __construct(ReservationRoomNight $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ReservationRoomNight
    {
        /** @var ReservationRoomNight|null */
        return parent::find($id);
    }

    public function store(array $data): ReservationRoomNight
    {
        /** @var ReservationRoomNight */
        return parent::store($data);
    }

    public function findForRoomKeyedByDate(int $reservationRoomId): array
    {
        return $this->model
            ->newQuery()
            ->where('reservation_room_id', $reservationRoomId)
            ->get()
            ->keyBy(fn (ReservationRoomNight $night): string => $night->date->toDateString())
            ->all();
    }
}
