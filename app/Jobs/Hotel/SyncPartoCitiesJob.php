<?php

namespace App\Jobs\Hotel;

use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderCityMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class SyncPartoCitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $provider = Provider::where('code', 'parto')->firstOrFail();

        $path = database_path('seeders/data/DomesticPropertyCity.json');

        if (!File::exists($path)) {
            \Log::error("Parto cities file not found", ['path' => $path]);
            return;
        }

        $json = File::get($path);
        $cities = json_decode($json, true);

        foreach ($cities as $c) {
            $city = City::updateOrCreate(
                ['fa_name' => $c['NameFa']], // شرط پیدا کردن
                [
                    'en_name'    => $c['Name'] ?? null,
                    'country_id' => 1, // ایران
                    'is_active'  => (bool)$c['IsActive'],
                    'is_popular' => (bool)$c['IsPopular'],
                ]
            );

            // مپ با پرتو
            ProviderCityMap::updateOrCreate(
                [
                    'provider_id'      => $provider->id,
                    'provider_city_id' => (string)($c['Id']),
                ],
                [
                    'city_id' => $city->id,
                    'fa_name' => $c['NameFa'],
                    'en_name' => $c['Name'] ?? null,
                ]
            );
        }

        \Log::info("Sync cities completed for Parto", [
            'count' => count($cities),
        ]);
    }
}
