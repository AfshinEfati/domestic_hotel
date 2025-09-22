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
}
