<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Throwable;

class HotelSyncCommand extends Command
{
    protected $signature = 'hotel:sync
        {provider : کد تأمین‌کننده مثل grs, iho, parto}
        {--days=30 : تعداد روزهای آینده برای گرفتن نرخ/ظرفیت}';

    protected $description = 'همگام‌سازی دیتا از تأمین‌کننده‌ها (شهرها، هتل‌ها، نرخ/ظرفیت)';

    /**
     * @param HotelSyncService $service
     * @return int
     */
    public function handle(HotelSyncService $service): int
    {
        $providerCode = $this->argument('provider');
        $days = (int)$this->option('days');

        $provider = Provider::query()->where('code', $providerCode)->first();
        if (!$provider) {
            $this->error("Provider {$providerCode} not found.");
            return self::FAILURE;
        }

        try {
            /** @var ProviderAdapterInterface $adapter */
            $adapter = app()->makeWith(ProviderAdapterInterface::class, [
                'provider' => $provider,
            ]);
        } catch (BindingResolutionException $e) {
            $this->error("Failed to resolve adapter for provider {$providerCode}: {$e->getMessage()}");
            return self::FAILURE;
        }

        try {
            $this->info("Syncing cities for provider: {$providerCode}");
            $service->syncCities($provider, $adapter);
        } catch (Throwable $e) {
            $this->error("Failed to sync cities: {$e->getMessage()}");
            return self::FAILURE;
        }

        try {
            $this->info("Syncing properties...");
            $providerCityIds = DB::table('provider_city_maps')
                ->where('provider_id', $provider->id)
                ->lazy()
                ->pluck('provider_city_id');

            $bar = $this->output->createProgressBar($providerCityIds->count());
            $bar->start();

            foreach ($providerCityIds as $providerCityId) {
                try {
                    $service->syncPropertiesForCity($provider, $adapter, (string)$providerCityId);
                } catch (Throwable $e) {
                    $this->warn("Failed to sync properties for city {$providerCityId}: {$e->getMessage()}");
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        } catch (Throwable $e) {
            $this->error("Error during properties sync: {$e->getMessage()}");
            return self::FAILURE;
        }

        try {
            $from = CarbonImmutable::today();
            $to = $from->addDays($days);

            $this->info("Crawling availability {$days} days...");
            $providerPropertyIds = DB::table('accommodation_provider_maps')
                ->where('provider_id', $provider->id)
                ->lazy()
                ->pluck('provider_property_id');

            $bar = $this->output->createProgressBar($providerPropertyIds->count());
            $bar->start();

            foreach ($providerPropertyIds as $ppid) {
                try {
                    $service->crawlAvailabilityForProperty($provider, $adapter, (string)$ppid, $from, $to);
                } catch (Throwable $e) {
                    $this->warn("Failed to crawl availability for property {$ppid}: {$e->getMessage()}");
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        } catch (Throwable $e) {
            $this->error("Error during availability crawl: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("Sync completed for provider: {$providerCode}");
        return self::SUCCESS;
    }
}
