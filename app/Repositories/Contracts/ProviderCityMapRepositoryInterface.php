<?php

namespace App\Repositories\Contracts;

use App\Models\ProviderCityMap;

interface ProviderCityMapRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<ProviderCityMap>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?ProviderCityMap;

    public function store(array $data): ProviderCityMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
