<?php

namespace App\Services;

use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;
use App\Services\Contracts\HotelRatePricingServiceInterface;

class HotelRatePricingService implements HotelRatePricingServiceInterface
{
    /** @var array<int, array{percentage: float, fixed_amount: int}> */
    private array $resolvedProviderRules = [];

    public function __construct(
        private readonly ProviderPricingRuleRepositoryInterface $pricingRuleRepository
    ) {}

    public function calculateFinalRate(int|float|null $baseRate, ?int $providerId = null): ?int
    {
        if ($baseRate === null) {
            return null;
        }

        $rule = $this->resolveRule($providerId);

        return (int) round(
            $baseRate
            + (($baseRate * $rule['percentage']) / 100)
            + $rule['fixed_amount']
        );
    }

    public function resolveRule(?int $providerId = null): array
    {
        $defaultRule = [
            'percentage' => (float) config('hotel.pricing.default_percentage', 5),
            'fixed_amount' => (int) config('hotel.pricing.default_fixed_amount', 0),
        ];

        if ($providerId === null) {
            return $defaultRule;
        }

        if (isset($this->resolvedProviderRules[$providerId])) {
            return $this->resolvedProviderRules[$providerId];
        }

        $providerRule = $this->pricingRuleRepository->findActiveByProviderId($providerId);

        if ($providerRule === null) {
            return $this->resolvedProviderRules[$providerId] = $defaultRule;
        }

        return $this->resolvedProviderRules[$providerId] = [
            'percentage' => (float) $providerRule->percentage,
            'fixed_amount' => (int) $providerRule->fixed_amount,
        ];
    }
}
