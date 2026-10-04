<?php

namespace App\Modules\HotelProviders\V2\Shared;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use Closure;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

final class HotelProviderRegistry
{
    /** @var array<string, Closure(Provider, Container): ProviderAdapterInterface> */
    private array $adapterFactories = [];

    /** @var array<string, class-string<PriceRefreshSchedulerHandler>> */
    private array $priceRefreshHandlers = [];

    public function registerAdapter(string $providerCode, Closure $factory): void
    {
        $this->adapterFactories[$providerCode] = $factory;
    }

    /** @param class-string<PriceRefreshSchedulerHandler> $handlerClass */
    public function registerPriceRefreshHandler(string $providerCode, string $handlerClass): void
    {
        $this->priceRefreshHandlers[$providerCode] = $handlerClass;
    }

    public function resolveAdapter(Provider $provider, Container $container): ProviderAdapterInterface
    {
        $factory = $this->adapterFactories[(string) $provider->code] ?? null;

        if ($factory === null) {
            throw new RuntimeException("Unknown provider code: {$provider->code}");
        }

        $adapter = $factory($provider, $container);

        if (!$adapter instanceof ProviderAdapterInterface) {
            throw new RuntimeException("Invalid provider adapter registered for {$provider->code}.");
        }

        return $adapter;
    }

    /** @return class-string<PriceRefreshSchedulerHandler>|null */
    public function priceRefreshHandler(string $providerCode): ?string
    {
        return $this->priceRefreshHandlers[$providerCode] ?? null;
    }
}
