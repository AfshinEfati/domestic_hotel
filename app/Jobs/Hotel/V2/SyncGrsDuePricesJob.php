<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/** Manual GRS trigger using the same coordinated provider-state flow as the scheduler. */
class SyncGrsDuePricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;
    public int $uniqueFor = 600;

    public function __construct(public ?int $days = null)
    {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        return 'grs-due-prices-dispatch';
    }

    public function handle(
        GrsPriceRefreshScheduleService $schedules,
        ?ProviderPriceRefreshScheduler $scheduler = null,
    ): void {
        $provider = $schedules->grsProvider();
        if ($provider === null) {
            throw new RuntimeException('GRS provider is not configured.');
        }
        if (!$provider->is_active) {
            return;
        }

        $days = $this->days ?? GrsRefreshSettings::from($provider)['default_days'];
        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('GRS availability days must be between 1 and 3650.');
        }

        if (RateLimitedGrsAdapter::cooldownSeconds() > 0) {
            return;
        }

        $scheduler ??= app(ProviderPriceRefreshScheduler::class);
        $scheduler->dispatch('grs', $days);
    }
}
