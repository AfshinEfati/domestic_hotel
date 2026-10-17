<?php

namespace App\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class HotelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HotelProviderRegistry::class, function (): HotelProviderRegistry {
            $registry = new HotelProviderRegistry();
            $registry->registerAdapter('parto', static fn (Provider $provider, Container $container) => new PartoAdapter($provider));
            $registry->registerAdapter('iho', static fn (Provider $provider, Container $container) => new IHOAdapter($provider));

            return $registry;
        });

        $this->app->bind(ProviderAdapterInterface::class, function ($app, array $params) {
            /** @var Provider|null $provider */
            $provider = $params['provider'] ?? null;
            if (!$provider instanceof Provider) {
                throw new InvalidArgumentException('Provider instance is required to resolve adapter.');
            }

            return $app->make(HotelProviderRegistry::class)->resolveAdapter($provider, $app);
        });
    }
}
