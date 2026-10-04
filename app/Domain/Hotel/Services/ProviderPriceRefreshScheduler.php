<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use RuntimeException;

/** One provider read per tick; individual modules own settings, jobs and API rules. */
class ProviderPriceRefreshScheduler
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providers,
        private readonly HotelProviderRegistry $registry,
    ) {
    }

    public function dispatch(): int
    {
        $eligible = 0;
        foreach ($this->providers->getAll() as $provider) {
            $handlerClass = $this->registry->priceRefreshHandler((string) $provider->code);
            if ($handlerClass === null) {
                continue;
            }

            $handler = app($handlerClass);
            if (!$handler instanceof PriceRefreshSchedulerHandler) {
                throw new RuntimeException("Invalid price refresh scheduler handler for {$provider->code}.");
            }
            if ($handler->dispatchIfEnabled($provider)) {
                $eligible++;
            }
        }

        return $eligible;
    }
}
