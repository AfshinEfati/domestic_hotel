<?php

namespace App\Repositories\Contracts;

use App\Models\RoomType;

interface RoomTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RoomType>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RoomType;

    public function store(array $data): RoomType;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
