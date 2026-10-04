<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class HealthCommand extends Command
{
    protected $signature = 'snapptrip:health';
    protected $description = 'Check the SnappTrip B2B API health endpoint when provider outbound traffic is enabled.';

    public function handle(
        ProviderOutboundGuard $guard,
        SnappTripGatewayFactory $gateways,
    ): int {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');

            return self::FAILURE;
        }

        $provider = $guard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $response = $gateways->make($provider)->health();
        $this->line($response === [] ? 'SnappTrip health endpoint responded successfully.' : json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
