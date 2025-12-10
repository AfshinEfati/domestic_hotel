<?php

namespace App\Services\Contracts;

use App\DTOs\FacilityDTO;
use App\Models\Facility;

interface FacilityServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<Facility>
     */
    public function index(): iterable;

    public function show(int|string $id): ?Facility;

    /**
     * @param FacilityDTO|array $payload
     * @return Facility
     */
    public function store(mixed $payload): Facility;

    /**
     * @param int|string $id
     * @param FacilityDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
