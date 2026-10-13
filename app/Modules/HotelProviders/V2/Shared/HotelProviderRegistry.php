<?php

namespace App\Modules\HotelProviders\V2\Shared;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use Closure;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

final class HotelProviderRegistry
{
    /** @var array<string, Closure(Provider, Container): ProviderAdapterInterface> */
    private array $adapterFactories = [];

    /** @var array<string, class-string<PriceRefreshSchedulerHandler>> */
    private array $priceRefreshHandlers = [];

    /** @var array<string, class-string<OnlinePurchaseHandler>> */
    private array $onlinePurchaseHandlers = [];

    /** @var array<string, class-string<OnlinePurchasePreflightHandler>> */
    private array $onlinePurchasePreflightHandlers = [];

    public function registerAdapter(string $providerCode, Closure $factory): void
    {
        $this->adapterFactories[$providerCode] = $factory;
    }

    /** @param class-string<PriceRefreshSchedulerHandler> $handlerClass */
    public function registerPriceRefreshHandler(string $providerCode, string $handlerClass): void
    {
        $this->priceRefreshHandlers[$providerCode] = $handlerClass;
    }

    /** @param class-string<OnlinePurchaseHandler> $handlerClass */
    public function registerOnlinePurchaseHandler(string $providerCode, string $handlerClass): void
    {
        $this->onlinePurchaseHandlers[$providerCode] = $handlerClass;
    }

    /** @param class-string<OnlinePurchasePreflightHandler> $handlerClass */
    public function registerOnlinePurchasePreflightHandler(string $providerCode, string $handlerClass): void
    {
        $this->onlinePurchasePreflightHandlers[$providerCode] = $handlerClass;
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

    public function resolveOnlinePurchaseHandler(
        Provider $provider,
        Container $container,
    ): ?OnlinePurchaseHandler {
        $handlerClass = $this->onlinePurchaseHandlers[(string) $provider->code] ?? null;
        if ($handlerClass === null) {
            return null;
        }

        $handler = $container->make($handlerClass);
        if (!$handler instanceof OnlinePurchaseHandler) {
            throw new RuntimeException("Invalid online purchase handler registered for {$provider->code}.");
        }

        return $handler;
    }

    public function resolveOnlinePurchasePreflightHandler(
        Provider $provider,
        Container $container,
    ): ?OnlinePurchasePreflightHandler {
        $handlerClass = $this->onlinePurchasePreflightHandlers[(string) $provider->code] ?? null;
        if ($handlerClass === null) {
            return null;
        }

        $handler = $container->make($handlerClass);
        if (!$handler instanceof OnlinePurchasePreflightHandler) {
            throw new RuntimeException("Invalid online purchase preflight handler registered for {$provider->code}.");
        }

        return $handler;
    }
}
