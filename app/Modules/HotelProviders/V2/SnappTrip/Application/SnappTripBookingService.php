<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use RuntimeException;

final class SnappTripBookingService
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
    ) {
    }

    public function create(array $payload): array
    {
        $provider = $this->providerForOnlinePurchase();

        return $this->gateways->make($provider)->createBooking($payload);
    }

    public function lock(string $reservationCode): void
    {
        $provider = $this->providerForOnlinePurchase();
        $this->gateways->make($provider)->lockBooking($reservationCode);
    }

    public function confirm(string $reservationCode): array
    {
        $provider = $this->providerForOnlinePurchase();
        $gateway = $this->gateways->make($provider);
        $gateway->confirmBooking($reservationCode);

        // SnappTrip requires checking booking state after confirm. The GET result,
        // rather than the confirm HTTP status alone, is authoritative here.
        return $gateway->booking($reservationCode);
    }

    public function status(string $reservationCode): array
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);

        return $this->gateways->make($provider)->booking($reservationCode);
    }

    private function providerForOnlinePurchase(): Provider
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        if (data_get(SnappTripSettings::from($provider), 'purchase.online_enabled') !== true) {
            throw new RuntimeException('SnappTrip online purchase is disabled; use the manual purchase workflow.');
        }

        return $provider;
    }
}
