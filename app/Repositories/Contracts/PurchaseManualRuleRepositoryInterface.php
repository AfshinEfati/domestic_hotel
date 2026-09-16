<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseManualRule;

interface PurchaseManualRuleRepositoryInterface extends BaseRepositoryInterface
{
    public function findMatching(
        int $providerId,
        int $accommodationId,
        int $saleAmount,
        string $localTime
    ): ?PurchaseManualRule;
}
