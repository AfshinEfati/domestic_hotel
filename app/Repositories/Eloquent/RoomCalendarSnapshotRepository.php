<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomCalendarSnapshot;
use App\Repositories\Contracts\RoomCalendarSnapshotRepositoryInterface;

class RoomCalendarSnapshotRepository extends BaseRepository implements RoomCalendarSnapshotRepositoryInterface
{
    public function __construct(RoomCalendarSnapshot $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RoomCalendarSnapshot>
     */
    public function getAll(): iterable
    {
        /** @var iterable<RoomCalendarSnapshot> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomCalendarSnapshot
    {
        /** @var RoomCalendarSnapshot|null */
        return parent::find($id);
    }

    public function store(array $data): RoomCalendarSnapshot
    {
        /** @var RoomCalendarSnapshot */
        return parent::store($data);
    }
}
