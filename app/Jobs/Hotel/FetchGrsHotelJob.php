<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchGrsHotelJob implements ShouldQueue
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
        $properties = $adapter->fetchProperties();

        $this->dispatchPropertyJobs($properties);
    }

    /**
     * @param iterable<int, array<string, mixed>> $properties
     */
    private function dispatchPropertyJobs(iterable $properties): void
    {
        foreach ($properties as $property) {
            if (!is_array($property)) {
                continue;
            }
            if ($property['country_id']!==222) {
                continue;
            }
            ProcessGrsHotelPropertyJob::dispatch($this->providerCode, $property);
        }
    }
}
