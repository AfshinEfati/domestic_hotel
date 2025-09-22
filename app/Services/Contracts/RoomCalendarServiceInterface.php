<?php

namespace App\Services\Contracts;

use App\DTOs\RoomCalendarDTO;
use App\Models\RoomCalendar;

interface RoomCalendarServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RoomCalendar>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RoomCalendar;

    /**
     * @param RoomCalendarDTO|array $payload
     * @return RoomCalendar
     */
    public function store(mixed $payload): RoomCalendar;

    /**
     * @param int|string $id
     * @param RoomCalendarDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
