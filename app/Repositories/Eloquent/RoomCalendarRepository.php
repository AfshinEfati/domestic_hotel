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
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomCalendar
    {
        return parent::find($id);
    }

    public function store(array $data): RoomCalendar
    {
        return parent::store($data);
    }

    public function getAvailableByAccommodationId(
        int $accommodationId,
        string $checkIn,
        string $checkOut
    ): Collection {
        return $this->model
            ->newQuery()
            ->with([
                'roomType.roomTypeName',
                'ratePlan',
            ])
            ->where('accommodation_id', $accommodationId)
            ->whereDate('day', '>=', $checkIn)
            ->whereDate('day', '<', $checkOut)
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

    public function getByRoomTypeIdsAndDays(
        array $roomTypeIds,
        array $days
    ): Collection {
        if ($roomTypeIds === [] || $days === []) {
            return $this->model->newCollection();
        }

        return $this->model
            ->newQuery()
            ->whereIn(
                'room_type_id',
                array_values(array_unique($roomTypeIds))
            )
            ->whereIn(
                'day',
                array_values(array_unique($days))
            )
            ->orderBy('id')
            ->get();
    }

    public function getForAvailability(
        array $accommodationIds,
        string $checkIn,
        string $checkOut
    ): Collection {
        if ($accommodationIds === []) {
            return $this->model->newCollection();
        }

        return $this->model
            ->newQuery()
            ->with('ratePlan')
            ->whereIn(
                'accommodation_id',
                array_values(array_unique($accommodationIds))
            )
            ->where('day', '>=', $checkIn)
            ->where('day', '<', $checkOut)
            ->orderBy('room_type_id')
            ->orderBy('provider_id')
            ->orderBy('rate_plan_id')
            ->orderBy('day')
            ->orderBy('id')
            ->get();
    }
}
