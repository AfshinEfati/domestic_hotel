<?php

namespace App\Jobs\Hotel;

use App\Services\Contracts\AccommodationProviderMapServiceInterface;
use App\Services\Contracts\ProviderServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Hotel\SyncRoomsForHotelJob;

class DispatchRoomSyncJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $providerCode;

    public function __construct(string $providerCode)
    {
        $this->providerCode = $providerCode;
    }

    public function handle(
        ProviderServiceInterface $providerService,
        AccommodationProviderMapServiceInterface $mapService
    ): void
    {
        $provider = $providerService->repository()->findDynamic(['code' => $this->providerCode]);

        if (!$provider) {
            return;
        }

        // Get all mapped accommodations for this provider
        // We only want to sync rooms for hotels that are already mapped/synced
        $mapService->chunkByProvider($provider->id, function ($maps) use ($provider) {
            foreach ($maps as $map) {
                SyncRoomsForHotelJob::dispatch($provider->id, $map->accommodation_id, $map->provider_accommodation_id);
            }
        });
    }
}
