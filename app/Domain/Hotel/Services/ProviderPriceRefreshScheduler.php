<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use RuntimeException;

/** One provider read per tick; individual handlers own their own pricing rules. */
class ProviderPriceRefreshScheduler
{
    /** @var array<string, class-string<PriceRefreshSchedulerHandler>> */
    private const HANDLERS = [
        'grs' => GrsScheduledPriceRefresh::class,
    ];

    public function __construct(private readonly ProviderRepositoryInterface $providers)
    {
    }

    public function dispatch(): int
    {
        $eligible = 0;
        // getAll() runs one repository query; no per-provider DB lookup in the scheduler.
        foreach ($this->providers->getAll() as $provider) {
            $handlerClass = self::HANDLERS[(string) $provider->code] ?? null;
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
