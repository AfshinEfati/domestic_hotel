<?php

namespace App\Repositories\Contracts;

use App\Models\ProviderPricingRule;

interface ProviderPricingRuleRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?ProviderPricingRule;

    public function store(array $data): ProviderPricingRule;

    public function findActiveByProviderId(int $providerId): ?ProviderPricingRule;
}
