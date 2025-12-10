<?php

namespace App\Repositories\Contracts;

use App\Models\Provider;

interface ProviderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<Provider>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?Provider;

    public function store(array $data): Provider;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
