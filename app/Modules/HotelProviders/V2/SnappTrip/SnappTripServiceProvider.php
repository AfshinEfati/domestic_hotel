<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Console\CalendarWindowCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\HealthCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\ProbeCalendarCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\SyncBalanceCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\SyncCancellationsCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\SyncCatalogCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\SyncHotelDetailsCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Console\SyncPricesCommand;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncCatalogJob;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncPendingCancellationsJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

final class SnappTripServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(
            HotelProviderRegistry::class,
            function (HotelProviderRegistry $registry): void {
                $registry->registerPriceRefreshHandler(
                    SnappTripSettings::PROVIDER_CODE,
                    SnappTripScheduledPriceRefresh::class,
                );
            },
        );
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CalendarWindowCommand::class,
            HealthCommand::class,
            ProbeCalendarCommand::class,
            SyncCatalogCommand::class,
            SyncHotelDetailsCommand::class,
            SyncPricesCommand::class,
            SyncBalanceCommand::class,
            SyncCancellationsCommand::class,
        ]);

        // SnappTrip recommends refreshing static hotel data every 24 hours or more.
        Schedule::call(function (): void {
            $guard = app(ProviderOutboundGuard::class);
            $provider = $guard->provider(SnappTripSettings::PROVIDER_CODE);
            if (
                $provider !== null
                && $guard->allows($provider)
                && data_get(SnappTripSettings::from($provider), 'static_sync.scheduler_enabled') === true
            ) {
                SyncCatalogJob::dispatch()->onQueue('snapptrip-static');
            }
        })
            ->name('snapptrip-v2-static-catalog')
            ->dailyAt('03:30')
            ->timezone('Asia/Tehran')
            ->withoutOverlapping();

        Schedule::call(function (): void {
            $guard = app(ProviderOutboundGuard::class);
            if ($guard->allows(SnappTripSettings::PROVIDER_CODE)) {
                SyncPendingCancellationsJob::dispatch()->onQueue('snapptrip-operations');
            }
        })
            ->name('snapptrip-v2-pending-cancellations')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }
}
