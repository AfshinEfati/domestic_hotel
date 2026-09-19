<?php

namespace App\Repositories\Contracts;

use App\Models\RoomTypeProviderMap;
use Illuminate\Support\Collection;

interface RoomTypeProviderMapRepositoryInterface extends BaseRepositoryInterface
{
    /** @return iterable<RoomTypeProviderMap> */
    public function getAll(): iterable;

    public function find(int|string $id): ?RoomTypeProviderMap;

    /** @param array<int, string> $providerRoomIds
     *  @return Collection<string, RoomTypeProviderMap>
     */
    public function mappedForProviderIds(int $providerId, array $providerRoomIds): Collection;

    public function store(array $data): RoomTypeProviderMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
