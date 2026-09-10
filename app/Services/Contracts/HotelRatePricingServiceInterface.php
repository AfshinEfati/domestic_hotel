<?php

namespace App\Services\Contracts;

interface HotelRatePricingServiceInterface
{
    public function calculateFinalRate(int|float|null $baseRate, ?int $providerId = null): ?int;

    /**
     * @return array{percentage: float, fixed_amount: int}
     */
    public function resolveRule(?int $providerId = null): array;
}
