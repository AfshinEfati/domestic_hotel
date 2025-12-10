<?php

namespace App\Services\Contracts;

use App\DTOs\ProviderCityMapDTO;
use App\Models\ProviderCityMap;

interface ProviderCityMapServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<ProviderCityMap>
     */
    public function index(): iterable;

    public function show(int|string $id): ?ProviderCityMap;

    /**
     * @param ProviderCityMapDTO|array $payload
     * @return ProviderCityMap
     */
    public function store(mixed $payload): ProviderCityMap;

    /**
     * @param int|string $id
     * @param ProviderCityMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
