<?php

namespace App\Repositories\Contracts;

use App\Models\Accommodation;

interface AccommodationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<Accommodation>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?Accommodation;

    public function store(array $data): Accommodation;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
    public function getList(array $filters): iterable;
}
