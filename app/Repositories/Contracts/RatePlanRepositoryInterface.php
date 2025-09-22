<?php

namespace App\Repositories\Contracts;

use App\Models\RatePlan;

interface RatePlanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<RatePlan>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?RatePlan;

    public function store(array $data): RatePlan;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
