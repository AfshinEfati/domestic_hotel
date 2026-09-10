<?php

namespace App\Services;

use App\DTOs\ProviderPricingRuleDTO;
use App\Models\ProviderPricingRule;
use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;
use App\Services\Contracts\ProviderPricingRuleServiceInterface;

class ProviderPricingRuleService extends BaseService implements ProviderPricingRuleServiceInterface
{
    public function __construct(ProviderPricingRuleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function store(mixed $payload): ProviderPricingRule
    {
        if ($payload instanceof ProviderPricingRuleDTO) {
            $payload = $payload->toArray();
        }

        /** @var ProviderPricingRule */
        return parent::store($payload);
    }

    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof ProviderPricingRuleDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }
}
