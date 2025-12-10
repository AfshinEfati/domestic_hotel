<?php

namespace App\Services\Contracts;

use App\DTOs\RoomCalendarSnapshotDTO;
use App\Models\RoomCalendarSnapshot;

interface RoomCalendarSnapshotServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RoomCalendarSnapshot>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RoomCalendarSnapshot;

    /**
     * @param RoomCalendarSnapshotDTO|array $payload
     * @return RoomCalendarSnapshot
     */
    public function store(mixed $payload): RoomCalendarSnapshot;

    /**
     * @param int|string $id
     * @param RoomCalendarSnapshotDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
