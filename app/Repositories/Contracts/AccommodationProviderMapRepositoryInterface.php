<?php

namespace App\Repositories\Contracts;

use App\Models\AccommodationProviderMap;

interface AccommodationProviderMapRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return iterable<AccommodationProviderMap>
     */
    public function getAll(): iterable;

    public function find(int|string $id): ?AccommodationProviderMap;

    public function store(array $data): AccommodationProviderMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
