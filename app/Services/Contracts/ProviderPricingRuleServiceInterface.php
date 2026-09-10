<?php

namespace App\Services\Contracts;

use App\Models\ProviderPricingRule;

interface ProviderPricingRuleServiceInterface extends BaseServiceInterface
{
    public function store(mixed $payload): ProviderPricingRule;

    public function update(int|string $id, mixed $payload): bool;
}
