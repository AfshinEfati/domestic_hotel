<?php

namespace App\Repositories\Contracts;

use App\Models\FacilityGroup;

interface FacilityGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<FacilityGroup>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?FacilityGroup;

    public function store(array $data): FacilityGroup;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
    public function getList(array $filters = []): iterable;
}
