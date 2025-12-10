<?php

namespace App\Services\Contracts;

use App\Models\Accommodation;
use App\DTOs\AccommodationDTO;

interface AccommodationServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<Accommodation>
     */
    public function index(): iterable;

    public function show(int|string $id): ?Accommodation;

    /**
     * @param AccommodationDTO|array $payload
     * @return Accommodation
     */
    public function store(mixed $payload): Accommodation;

    /**
     * @param int|string $id
     * @param AccommodationDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
