<?php

namespace App\Services;

use App\DTOs\RoomCalendarDTO;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\Contracts\RoomCalendarServiceInterface;

class RoomCalendarService extends BaseService implements RoomCalendarServiceInterface
{
    public function __construct(RoomCalendarRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RoomCalendar>
     */
    public function index(): iterable
    {
        /** @var iterable<RoomCalendar> */
        return parent::index();
    }

    public function show(int|string $id): ?RoomCalendar
    {
        /** @var RoomCalendar|null */
        return parent::show($id);
    }

    /**
     * @param RoomCalendarDTO|array $payload
     * @return RoomCalendar
     */
    public function store(mixed $payload): RoomCalendar
    {
        if ($payload instanceof RoomCalendarDTO) {
            $payload = $payload->toArray();
        }

        /** @var RoomCalendar */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RoomCalendarDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RoomCalendarDTO) {
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
            'accommodation.city',
            'accommodation.type',
            'accommodation.facilities',
            'roomType.accommodation.city',
            'roomType.accommodation.type',
            'roomType.accommodation.facilities',
            'ratePlan.accommodation.city',
            'ratePlan.accommodation.type',
            'ratePlan.accommodation.facilities',
            'provider.cityMaps.city',
        ];
    }
}
