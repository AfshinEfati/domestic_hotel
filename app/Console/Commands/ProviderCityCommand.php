<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Hotel\SyncCitiesJob;

class ProviderCityCommand extends Command
{
    protected $signature = 'provider:city {provider}';
    protected $description = 'Dispatch a test job for hotel provider';

    public function handle(): void
    {
        $provider = $this->argument('provider');
        SyncCitiesJob::dispatch($provider);

        $this->info("Dispatched SyncCitiesJob for provider: {$provider}");
    }
}
