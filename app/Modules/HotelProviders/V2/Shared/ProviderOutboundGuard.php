<?php

namespace App\Modules\HotelProviders\V2\Shared;

use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use RuntimeException;

final class ProviderOutboundGuard
{
    public function __construct(private readonly ProviderRepositoryInterface $providers)
    {
    }

    public function provider(string $providerCode): ?Provider
    {
        return $this->providers->findByCode($providerCode);
    }

    public function allows(Provider|string $provider): bool
    {
        $model = $provider instanceof Provider ? $provider : $this->provider($provider);

        return $model !== null
            && $model->is_active === true
            && $model->is_online === true;
    }

    public function assertAllowed(Provider|string $provider): Provider
    {
        $model = $provider instanceof Provider ? $provider : $this->provider($provider);

        if ($model === null) {
            $code = $provider instanceof Provider ? (string) $provider->code : $provider;
            throw new RuntimeException("Provider {$code} is not configured.");
        }

        if (!$this->allows($model)) {
            throw new RuntimeException("Provider {$model->code} outbound operations are disabled.");
        }

        return $model;
    }
}
