<?php

namespace App\Repositories\Eloquent;

use App\Models\ProviderPricingRule;
use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;

class ProviderPricingRuleRepository extends BaseRepository implements ProviderPricingRuleRepositoryInterface
{
    public function __construct(ProviderPricingRule $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?ProviderPricingRule
    {
        /** @var ProviderPricingRule|null */
        return parent::find($id);
    }

    public function store(array $data): ProviderPricingRule
    {
        /** @var ProviderPricingRule */
        return parent::store($data);
    }

    public function findActiveByProviderId(int $providerId): ?ProviderPricingRule
    {
        return $this->model
            ->newQuery()
            ->where('provider_id', $providerId)
            ->where('is_active', true)
            ->first();
    }
}
