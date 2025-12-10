<?php

namespace App\Services;

use App\DTOs\RoomCalendarSnapshotDTO;
use App\Models\RoomCalendarSnapshot;
use App\Repositories\Contracts\RoomCalendarSnapshotRepositoryInterface;
use App\Services\Contracts\RoomCalendarSnapshotServiceInterface;

class RoomCalendarSnapshotService extends BaseService implements RoomCalendarSnapshotServiceInterface
{
    public function __construct(RoomCalendarSnapshotRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RoomCalendarSnapshot>
     */
    public function index(): iterable
    {
        /** @var iterable<RoomCalendarSnapshot> */
        return parent::index();
    }

    public function show(int|string $id): ?RoomCalendarSnapshot
    {
        /** @var RoomCalendarSnapshot|null */
        return parent::show($id);
    }

    /**
     * @param RoomCalendarSnapshotDTO|array $payload
     * @return RoomCalendarSnapshot
     */
    public function store(mixed $payload): RoomCalendarSnapshot
    {
        if ($payload instanceof RoomCalendarSnapshotDTO) {
            $payload = $payload->toArray();
        }

        /** @var RoomCalendarSnapshot */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RoomCalendarSnapshotDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RoomCalendarSnapshotDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    protected function relations(): array
    {
        return [
            'roomCalendar.accommodation.city',
            'roomCalendar.accommodation.type',
            'roomCalendar.accommodation.facilities',
            'roomCalendar.roomType.accommodation.city',
            'roomCalendar.roomType.accommodation.type',
            'roomCalendar.roomType.accommodation.facilities',
            'roomCalendar.ratePlan.accommodation.city',
            'roomCalendar.ratePlan.accommodation.type',
            'roomCalendar.ratePlan.accommodation.facilities',
            'roomCalendar.provider.cityMaps.city',
            'provider.cityMaps.city',
        ];
    }
}
