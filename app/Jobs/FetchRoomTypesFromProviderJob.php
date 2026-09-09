<?php

namespace App\Jobs;

use App\Models\AccommodationProviderMap;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Services\HotelDataSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $class = $provider->class;

        if (!class_exists($class)) {
            // کلاس پیدا نشد -> پایان جاب بدون خطا
            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = new $class($provider);

        try {
            $roomTypes = $adapter->fetchRoomTypes($this->map->provider_property_id);
        } catch (\Exception $e) {
            Log::error("API Error for provider {$provider->id}: " . $e->getMessage());
            $this->fail($e);
            return;
        }

        if (empty($roomTypes)) {
            return;
        }

        try {
            // ذخیره در دیتابیس
            /** @var HotelDataSyncService $syncService */
            $syncService = app(HotelDataSyncService::class);
            $syncService->syncRoomTypes($this->map, $roomTypes);
        } catch (\Exception $e) {
            Log::error("Sync Error for property {$this->map->provider_property_id}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
