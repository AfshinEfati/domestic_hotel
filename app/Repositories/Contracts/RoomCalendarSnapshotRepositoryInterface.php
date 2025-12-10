<?php

namespace App\Repositories\Contracts;

use App\Models\RoomCalendarSnapshot;

interface RoomCalendarSnapshotRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RoomCalendarSnapshot>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RoomCalendarSnapshot;

    public function store(array $data): RoomCalendarSnapshot;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
