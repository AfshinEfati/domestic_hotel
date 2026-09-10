<?php

namespace App\Services;

use App\Repositories\Contracts\ProviderPricingRuleRepositoryInterface;
use App\Services\Contracts\HotelRatePricingServiceInterface;
use App\Services\Contracts\SystemSettingServiceInterface;
use App\Support\System\SystemSettingKey;

class HotelRatePricingService implements HotelRatePricingServiceInterface
{
    /** @var array<int, array{percentage: float, fixed_amount: int}> */
    private array $resolvedProviderRules = [];

    /** @var array{percentage: float, fixed_amount: int}|null */
    private ?array $resolvedDefaultRule = null;

    public function __construct(
        private readonly ProviderPricingRuleRepositoryInterface $pricingRuleRepository,
        private readonly SystemSettingServiceInterface $systemSettingService,
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
        if ($providerId === null) {
            return $this->resolveDefaultRule();
        }

        if (isset($this->resolvedProviderRules[$providerId])) {
            return $this->resolvedProviderRules[$providerId];
        }

        $providerRule = $this->pricingRuleRepository->findActiveByProviderId($providerId);

        if ($providerRule === null) {
            return $this->resolvedProviderRules[$providerId] = $this->resolveDefaultRule();
        }

        return $this->resolvedProviderRules[$providerId] = [
            'percentage' => (float) $providerRule->percentage,
            'fixed_amount' => (int) $providerRule->fixed_amount,
        ];
    }

    private function resolveDefaultRule(): array
    {
        if ($this->resolvedDefaultRule !== null) {
            return $this->resolvedDefaultRule;
        }

        return $this->resolvedDefaultRule = [
            'percentage' => (float) $this->systemSettingService->getValue(
                SystemSettingKey::PRICING_DEFAULT_PERCENTAGE
            ),
            'fixed_amount' => (int) $this->systemSettingService->getValue(
                SystemSettingKey::PRICING_DEFAULT_FIXED_AMOUNT
            ),
        ];
    }
}
