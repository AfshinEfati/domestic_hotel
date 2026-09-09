<?php

namespace App\Repositories\Contracts;

use App\Models\RoomCalendar;
use Illuminate\Support\Collection;


interface RoomCalendarRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RoomCalendar>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RoomCalendar;

    public function store(array $data): RoomCalendar;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
    public function getAvailableByAccommodationId(int $accommodationId): Collection;
}
