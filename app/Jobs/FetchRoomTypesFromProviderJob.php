<?php

namespace App\Jobs;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\AccommodationProviderMap;
use App\Modules\HotelProviders\V2\Grs\Support\GrsSettings;
use App\Services\HotelDataSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchRoomTypesFromProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * تعیین اینکه این جاب فقط و فقط ۱ بار اجرا شود.
     * اگر در هر مرحله خطا بخورد (Exception)، مستقیماً Fail می‌شود و تکرار نمی‌گردد.
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(
        public AccommodationProviderMap $map
    ) {}

    // متدهای middleware و Redis::throttle حذف شدند چون کنترل ترافیک به مرحله Dispatch منتقل شد

    public function handle(): void
    {
        $provider = $this->map->provider;

        if ((string) $provider->code === GrsSettings::PROVIDER_CODE) {
            /** @var ProviderAdapterInterface $adapter */
            $adapter = app()->makeWith(ProviderAdapterInterface::class, [
                'provider' => $provider,
            ]);
        } else {
            $class = $provider->class;

            if (!is_string($class) || $class === '' || !class_exists($class)) {
                // کلاس پیدا نشد -> پایان جاب بدون خطا
                return;
            }

            /** @var ProviderAdapterInterface $adapter */
            $adapter = new $class($provider);
        }

        $roomTypes = $adapter->fetchRoomTypes($this->map->provider_property_id);

        if (empty($roomTypes)) {
            return;
        }

        /** @var HotelDataSyncService $syncService */
        $syncService = app(HotelDataSyncService::class);
        $syncService->syncRoomTypes($this->map, $roomTypes);
    }
}
