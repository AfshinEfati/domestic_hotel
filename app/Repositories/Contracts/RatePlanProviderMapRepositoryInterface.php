<?php

namespace App\Repositories\Contracts;

use App\Models\RatePlanProviderMap;

interface RatePlanProviderMapRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RatePlanProviderMap>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RatePlanProviderMap;

    public function store(array $data): RatePlanProviderMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
