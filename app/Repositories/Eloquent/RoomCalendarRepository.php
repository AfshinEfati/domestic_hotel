<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomCalendar;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;

class RoomCalendarRepository extends BaseRepository implements RoomCalendarRepositoryInterface
{
    public function __construct(RoomCalendar $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RoomCalendar>
     */
    public function getAll(): iterable
    {
        /** @var iterable<RoomCalendar> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomCalendar
    {
        /** @var RoomCalendar|null */
        return parent::find($id);
    }

    public function store(array $data): RoomCalendar
    {
        /** @var RoomCalendar */
        return parent::store($data);
    }
}
