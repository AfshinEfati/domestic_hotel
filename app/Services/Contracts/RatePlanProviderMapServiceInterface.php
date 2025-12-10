<?php

namespace App\Services\Contracts;

use App\DTOs\RatePlanProviderMapDTO;
use App\Models\RatePlanProviderMap;

interface RatePlanProviderMapServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RatePlanProviderMap>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RatePlanProviderMap;

    /**
     * @param RatePlanProviderMapDTO|array $payload
     * @return RatePlanProviderMap
     */
    public function store(mixed $payload): RatePlanProviderMap;

    /**
     * @param int|string $id
     * @param RatePlanProviderMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
