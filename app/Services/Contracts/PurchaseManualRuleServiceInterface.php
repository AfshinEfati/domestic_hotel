<?php

namespace App\Services\Contracts;

use App\Models\PurchaseManualRule;

interface PurchaseManualRuleServiceInterface extends BaseServiceInterface
{
    public function store(mixed $payload): PurchaseManualRule;

    public function update(int|string $id, mixed $payload): bool;
}
