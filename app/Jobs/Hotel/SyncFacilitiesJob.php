<?php

namespace App\Jobs\Hotel;

use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\Provider;
use App\Models\ProviderFacilityMap;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncFacilitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $providerCode;

    public function __construct(string $providerCode)
    {
        $this->providerCode = $providerCode;
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $provider = Provider::where('code', $this->providerCode)->firstOrFail();

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);

        $facilities = $adapter->fetchFacilities();

        foreach ($facilities as $f) {
            // 1) گروه
            $group = FacilityGroup::query()->firstOrCreate(
                ['fa_name' => $f['group_name']],
                ['en_name' => $f['group_name_en'] ?? null]
            );

            // 2) خود facility
            $facility = Facility::query()->updateOrCreate(
                [
                    'fa_name' => $f['name'],
                    'facility_group_id' => $group->id,
                ],
                [
                    'en_name' => $f['name_en'] ?? null,
                ]
            );

        }

        \Log::info("Facilities synced for provider {$provider->code}", [
            'count' => count($facilities),
        ]);
    }
}
