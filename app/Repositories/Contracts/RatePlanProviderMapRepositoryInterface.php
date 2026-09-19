<?php

namespace App\Repositories\Contracts;

use App\Models\RatePlanProviderMap;
use Illuminate\Support\Collection;

interface RatePlanProviderMapRepositoryInterface extends BaseRepositoryInterface
{
    /** @return iterable<RatePlanProviderMap> */
    public function getAll(): iterable;

    public function find(int|string $id): ?RatePlanProviderMap;

    /** @param array<int, string> $providerRateIds
     *  @return Collection<string, RatePlanProviderMap>
     */
    public function mappedForProviderIds(int $providerId, array $providerRateIds): Collection;

    public function store(array $data): RatePlanProviderMap;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
