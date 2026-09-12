<?php

namespace App\Console\Commands;

use App\Jobs\Hotel\DispatchRoomSyncJobs;
use App\Services\Contracts\ProviderServiceInterface;
use Illuminate\Console\Command;

class HotelSyncRoomsCommand extends Command
{
    protected $signature = 'hotel:sync-rooms {provider? : Provider code (optional)}';
    protected $description = 'Sync rooms for hotels from providers';

    public function handle(ProviderServiceInterface $providerService): int
    {
        $providerCode = $this->argument('provider');

        if ($providerCode) {
            $providers = $providerService->repository()->getByDynamic([
                'code' => $providerCode,
                'is_active' => true,
                'is_online' => true,
            ]);
        } else {
            $providers = $providerService->getActiveProviders();
        }

        if ($providers->isEmpty()) {
            $this->error('No active online providers found.');
            return self::FAILURE;
        }

        foreach ($providers as $provider) {
            $this->info("Dispatching room sync jobs for provider: {$provider->code}");
            DispatchRoomSyncJobs::dispatch($provider->code);
        }

        return self::SUCCESS;
    }
}
