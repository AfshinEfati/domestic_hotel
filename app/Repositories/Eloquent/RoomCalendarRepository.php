<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomCalendar;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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

    public function getAvailableByAccommodationId(int $accommodationId): Collection
    {
        return $this->model
            ->newQuery()
            ->with([
                'roomType.roomTypeName',
                'ratePlan',
            ])
            ->where('accommodation_id', $accommodationId)
            ->whereDate('day', '>=', now()->toDateString())
            ->where('closed', false)
            ->where('inventory', '>', 0)
            ->whereHas('roomType', function ($query) {
                $query->where('out_of_service', false);
            })
            ->orderBy('room_type_id')
            ->orderBy('rate_plan_id')
            ->orderBy('day')
            ->get();
    }
}
