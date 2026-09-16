<?php

namespace App\Repositories\Eloquent;

use App\Models\ProviderCreditBalance;
use App\Repositories\Contracts\ProviderCreditBalanceRepositoryInterface;

class ProviderCreditBalanceRepository extends BaseRepository implements ProviderCreditBalanceRepositoryInterface
{
    public function __construct(ProviderCreditBalance $model)
    {
        parent::__construct($model);
    }

    public function findByProviderId(int $providerId): ?ProviderCreditBalance
    {
        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->first();
    }
}
