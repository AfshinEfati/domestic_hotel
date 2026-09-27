<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseManualRule;

interface PurchaseManualRuleRepositoryInterface extends BaseRepositoryInterface
{
    /** @return iterable<PurchaseManualRule> */
    public function getAll(): iterable;

    public function find(int|string $id): ?PurchaseManualRule;

    public function store(array $data): PurchaseManualRule;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;

    public function findMatching(
        int $providerId,
        int $accommodationId,
        int $saleAmount,
        string $localTime
    ): ?PurchaseManualRule;
}
