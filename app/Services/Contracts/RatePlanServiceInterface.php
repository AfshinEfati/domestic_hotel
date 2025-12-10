<?php

namespace App\Services\Contracts;

use App\DTOs\RatePlanDTO;
use App\Models\RatePlan;

interface RatePlanServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RatePlan>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RatePlan;

    /**
     * @param RatePlanDTO|array $payload
     * @return RatePlan
     */
    public function store(mixed $payload): RatePlan;

    /**
     * @param int|string $id
     * @param RatePlanDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
