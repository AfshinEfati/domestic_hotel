<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use DB;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;

class HotelSyncCommand extends Command
{
    protected $signature = 'hotel:sync
        {provider : کد تأمین‌کننده مثل grs, iho, parto}
        {--days=30 : تعداد روزهای آینده برای گرفتن نرخ/ظرفیت}';

    protected $description = 'همگام‌سازی دیتا از تأمین‌کننده‌ها (شهرها، هتل‌ها، نرخ/ظرفیت)';

    /**
     * @throws BindingResolutionException
     */
    public function handle(HotelSyncService $service): int
    {
        $providerCode = $this->argument('provider');
        $days = (int)$this->option('days');

        $provider = Provider::where('code', $providerCode)->first();
        if (!$provider) {
            $this->error("Provider {$providerCode} not found.");
            return self::FAILURE;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->makeWith(ProviderAdapterInterface::class, [
            'provider' => $provider,
        ]);

        // ۱. سنک شهرها
        $this->info("Syncing cities for provider: {$providerCode}");
        $service->syncCities($provider, $adapter);

        // ۲. سنک هتل‌ها برای همه‌ی شهرهای provider
        $this->info("Syncing properties...");
        $providerCityIds = DB::table('provider_city_maps')
            ->where('provider_id', $provider->id)
            ->pluck('provider_city_id');

        foreach ($providerCityIds as $providerCityId) {
            $service->syncPropertiesForCity($provider, $adapter, (string)$providerCityId);
        }

        // ۳. کراول نرخ/ظرفیت
        $from = CarbonImmutable::today();
        $to   = $from->addDays($days);

        $this->info("Crawling availability {$days} days...");
        $providerPropertyIds = DB::table('accommodation_provider_maps')
            ->where('provider_id', $provider->id)
            ->pluck('provider_property_id');

        foreach ($providerPropertyIds as $ppid) {
            $service->crawlAvailabilityForProperty($provider, $adapter, (string)$ppid, $from, $to);
        }

        $this->info("Sync completed for provider: {$providerCode}");
        return self::SUCCESS;
    }
}
