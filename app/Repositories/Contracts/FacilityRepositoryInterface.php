<?php

namespace App\Repositories\Contracts;

use App\Models\Facility;

interface FacilityRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<Facility>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?Facility;

    public function store(array $data): Facility;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
    public function getList(array $filters = []): iterable;
}
