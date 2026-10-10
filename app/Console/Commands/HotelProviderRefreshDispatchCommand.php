<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use Illuminate\Console\Command;

class HotelProviderRefreshDispatchCommand extends Command
{
    protected $signature = 'hotel:provider-refresh-dispatch
        {--provider= : Optional provider code; omit to dispatch every registered provider}
        {--hotel= : Optional accommodation id; when set, queue only this due hotel}
        {--days= : Optional availability horizon override for manual runs}
        {--sync-only : Synchronize local provider states only; do not queue jobs or advance shared schedules}';

    protected $description = 'Synchronize due hotel/provider states and queue refreshes using provider-specific quotas.';

    public function handle(ProviderPriceRefreshScheduler $scheduler): int
    {
        $provider = trim((string) $this->option('provider'));
        $provider = $provider === '' ? null : $provider;

        $hotelOption = $this->option('hotel');
        $hotel = $hotelOption === null || $hotelOption === '' ? null : (int) $hotelOption;
        if ($hotel !== null && $hotel < 1) {
            $this->error('The --hotel value must be a positive accommodation id.');
            return self::INVALID;
        }

        $daysOption = $this->option('days');
        $days = $daysOption === null || $daysOption === '' ? null : (int) $daysOption;
        if ($days !== null && ($days < 1 || $days > 3650)) {
            $this->error('The --days value must be between 1 and 3650.');
            return self::INVALID;
        }

        if ((bool) $this->option('sync-only')) {
            if ($hotel !== null) {
                $this->error('--sync-only currently applies to the shared due set; omit --hotel for safe state synchronization.');
                return self::INVALID;
            }

            $count = $scheduler->synchronize($provider);
            $this->info("Synchronized {$count} local hotel/provider state(s). No provider jobs were queued and shared schedules were not advanced.");
            return self::SUCCESS;
        }

        if ($hotel !== null) {
            $count = $scheduler->dispatchAccommodation($hotel, $provider, $days);
            $scope = $provider === null ? 'all mapped providers' : "provider [{$provider}]";
            $this->info("Queued {$count} refresh job(s) for hotel [{$hotel}] and {$scope}.");
            return self::SUCCESS;
        }

        $count = $scheduler->dispatch($provider, $days);
        $scope = $provider === null ? 'all registered providers' : "provider [{$provider}]";
        $this->info("Queued {$count} hotel refresh job(s) for {$scope}.");

        return self::SUCCESS;
    }
}
