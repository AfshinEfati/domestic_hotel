<?php

namespace App\Services\Contracts;

use App\Models\AccommodationType;
use App\DTOs\AccommodationTypeDTO;

interface AccommodationTypeServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<AccommodationType>
     */
    public function index(): iterable;

    public function show(int|string $id): ?AccommodationType;

    /**
     * @param AccommodationTypeDTO|array $payload
     * @return AccommodationType
     */
    public function store(mixed $payload): AccommodationType;

    /**
     * @param int|string $id
     * @param AccommodationTypeDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
