<?php

namespace App\Repositories\Contracts;

use App\Models\AccommodationProviderMap;
use Illuminate\Support\Collection;

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

    public function countMappedPropertiesByProvider(int $providerId): int;

    public function existsForAccommodationAndProvider(int $accommodationId, int $providerId): bool;

    /**
     * @param callable(Collection<int, AccommodationProviderMap>): void $callback
     */
    public function chunkMappedPropertiesByProvider(int $providerId, int $chunkSize, callable $callback): void;

    public function chunkByProvider(int $providerId, callable $callback): void;

    public function chunkActive(callable $callback): void;
}
