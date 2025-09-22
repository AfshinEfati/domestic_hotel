<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CrawlAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Provider $provider,
        public string $providerPropertyId,
        public string $from,
        public string $to
    ) {}

    public function handle(HotelSyncService $service, ProviderAdapterInterface $adapter): void
    {
        $from = new CarbonImmutable($this->from);
        $to = new CarbonImmutable($this->to);

        $service->crawlAvailabilityForProperty(
            $this->provider,
            $adapter,
            $this->providerPropertyId,
            $from,
            $to
        );
    }
}
