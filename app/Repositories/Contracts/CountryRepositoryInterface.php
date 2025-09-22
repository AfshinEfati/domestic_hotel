<?php

namespace App\Repositories\Contracts;

use App\Models\Country;

interface CountryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<Country>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?Country;

    public function store(array $data): Country;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
