<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Contracts\AccommodationProviderMapServiceInterface;

class DispatchRoomTypeFetchJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AccommodationProviderMapServiceInterface $accProviderMapService): void
    {
        $accProviderMapService->chunkActive(function ($maps) {
            $count = 0;
            $delaySeconds = 0;

            foreach ($maps as $map) {
                // هر ۵ جاب که ارسال شد، ۱۲۰ ثانیه به تاخیر جاب‌های بعدی اضافه می‌کنیم
                if ($count > 0 && $count % 5 === 0) {
                    $delaySeconds += 120;
                }

                if ($delaySeconds > 0) {
                    FetchRoomTypesFromProviderJob::dispatch($map)->delay(now()->addSeconds($delaySeconds));
                } else {
                    FetchRoomTypesFromProviderJob::dispatch($map);
                }

                $count++;
            }
        });
    }
}
