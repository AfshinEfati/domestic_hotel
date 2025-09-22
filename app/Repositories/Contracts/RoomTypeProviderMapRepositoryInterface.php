<?php

namespace App\Repositories\Contracts;

use App\Models\RoomTypeProviderMap;

interface RoomTypeProviderMapRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RoomTypeProviderMap>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RoomTypeProviderMap;

    public function store(array $data): RoomTypeProviderMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
