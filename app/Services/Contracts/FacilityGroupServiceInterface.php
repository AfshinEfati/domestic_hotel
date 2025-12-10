<?php

namespace App\Services\Contracts;

use App\DTOs\FacilityGroupDTO;
use App\Models\FacilityGroup;

interface FacilityGroupServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<FacilityGroup>
     */
    public function index(): iterable;

    public function show(int|string $id): ?FacilityGroup;

    /**
     * @param FacilityGroupDTO|array $payload
     * @return FacilityGroup
     */
    public function store(mixed $payload): FacilityGroup;

    /**
     * @param int|string $id
     * @param FacilityGroupDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
