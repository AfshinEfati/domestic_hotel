<?php

namespace App\Services\Contracts;

use App\DTOs\AccommodationProviderMapDTO;
use App\Models\AccommodationProviderMap;

interface AccommodationProviderMapServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<AccommodationProviderMap>
     */
    public function index(): iterable;

    public function show(int|string $id): ?AccommodationProviderMap;

    /**
     * @param AccommodationProviderMapDTO|array $payload
     * @return AccommodationProviderMap
     */
    public function store(mixed $payload): AccommodationProviderMap;

    /**
     * @param int|string $id
     * @param AccommodationProviderMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;

    public function countMappedPropertiesByProvider(int $providerId): int;

    public function chunkMappedPropertiesByProvider(int $providerId, int $chunkSize, callable $callback): void;

    public function chunkByProvider(int $providerId, callable $callback): void;

    public function chunkActive(callable $callback): void;
}
