<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Models\Provider;

class HotelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderAdapterInterface::class, function ($app, array $params) {
            /** @var Provider $provider */
            $provider = $params['provider'] ?? null;

            if (!$provider) {
                throw new \InvalidArgumentException("Provider instance is required to resolve adapter");
            }

            return match ($provider->code) {
                'grs'   => new GRSAdapter($provider),
                'parto' => new PartoAdapter($provider),
                'iho'   => new IHOAdapter($provider),
                default => throw new \RuntimeException("Unknown provider code: {$provider->code}")
            };
        });
    }
}
