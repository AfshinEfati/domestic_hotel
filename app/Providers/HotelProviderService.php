<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;

class HotelProviderService extends ServiceProvider
{
    public function register(): void
    {
        // contextual binding when resolved with ['provider' => Provider]
        $this->app->bind(ProviderAdapterInterface::class, function ($app, $params) {
            /** @var Provider $provider */
            $provider = $params['provider'] ?? null;
            if (!$provider) {
                throw new \InvalidArgumentException('Provider is required to resolve adapter.');
            }

            return match ($provider->code) {
                'grs'       => new GRSAdapter($provider),
                'iho'       => new IHOAdapter($provider),
                'parto'     => new PartoAdapter($provider),
                'snapptrip' => new SnappTripAdapter($provider),
                default     => throw new \RuntimeException("Unknown provider code: {$provider->code}"),
            };
        });
    }
}
