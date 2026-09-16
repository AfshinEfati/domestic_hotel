<?php

namespace App\Repositories\Contracts;

use App\Models\ProviderCreditBalance;

interface ProviderCreditBalanceRepositoryInterface extends BaseRepositoryInterface
{
    public function findByProviderId(int $providerId): ?ProviderCreditBalance;
}
