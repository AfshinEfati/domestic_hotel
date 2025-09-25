<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Support\Logging\SystemLogger;

use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Throwable;

class SyncGrsAvailabilityForPropertyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $providerId,
        public string $providerPropertyId,
        public string $fromDate,
        public string $toDate,
        public int $maxAttempts,
        public int $throttleMs
    ) {
    }

    public function handle(HotelSyncService $service, SystemLogger $logger): void
    {
        $provider = Provider::find($this->providerId);
        if (!$provider) {
            $logger->warning(__METHOD__, 'SyncGrsAvailabilityForPropertyJob skipped because provider not found', [

                'provider_id' => $this->providerId,
                'provider_property_id' => $this->providerPropertyId,
            ]);

            return;
        }

        $propertyKey = trim($this->providerPropertyId);
        if ($propertyKey === '') {
            $logger->warning(__METHOD__, 'SyncGrsAvailabilityForPropertyJob skipped because provider property id is empty', [

                'provider_id' => $this->providerId,
            ]);

            return;
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->makeWith(ProviderAdapterInterface::class, [
            'provider' => $provider,
        ]);

        $from = CarbonImmutable::parse($this->fromDate);
        $to = CarbonImmutable::parse($this->toDate);

        $this->syncPropertyWithRetry(
            $service,
            $provider,
            $adapter,
            $propertyKey,
            $from,
            $to,
            max(1, $this->maxAttempts),
            max(0, $this->throttleMs),
            $logger

        );
    }

    private function syncPropertyWithRetry(
        HotelSyncService $service,
        Provider $provider,
        ProviderAdapterInterface $adapter,
        string $propertyKey,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $maxAttempts,
        int $throttleMs,
        SystemLogger $logger

    ): void {
        $attempt = 0;
        $lastRequestAt = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $this->enforceThrottle($lastRequestAt, $throttleMs);

            try {
                $service->crawlAvailabilityForProperty(
                    $provider,
                    $adapter,
                    $propertyKey,
                    $from,
                    $to
                );

                return;
            } catch (Throwable $exception) {
                $logger->warning(__METHOD__, 'Failed to sync GRS availability for property', [

                    'provider_id' => $provider->id,
                    'provider_property_id' => $propertyKey,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);

                if ($attempt >= $maxAttempts) {
                    $logger->error(__METHOD__, 'Abandoning GRS availability sync after max attempts', [

                        'provider_id' => $provider->id,
                        'provider_property_id' => $propertyKey,
                        'attempts' => $attempt,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    private function enforceThrottle(?float &$lastRequestAt, int $throttleMs): void
    {
        if ($throttleMs <= 0) {
            $lastRequestAt = microtime(true);

            return;
        }

        if ($lastRequestAt !== null) {
            $elapsedMs = (microtime(true) - $lastRequestAt) * 1000;
            $remaining = (int)max(0, ($throttleMs - $elapsedMs) * 1000);
            if ($remaining > 0) {
                usleep($remaining);
            }
        }

        $lastRequestAt = microtime(true);
    }
}
