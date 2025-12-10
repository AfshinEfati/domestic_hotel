<?php

namespace App\Repositories\Contracts;

use App\Models\State;

interface StateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<State>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?State;

    public function store(array $data): State;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
