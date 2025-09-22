<?php

namespace App\Repositories\Contracts;

use App\Models\City;

interface CityRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<City>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?City;

    public function store(array $data): City;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
