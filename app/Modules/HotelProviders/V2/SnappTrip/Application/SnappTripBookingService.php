<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGateway;
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

    public function create(array $payload, ?int $reservationId = null, int $attempt = 1): array
    {
        return $this->gatewayForOnlinePurchase($reservationId, __FUNCTION__, $attempt)
            ->createBooking($payload);
    }

    public function lock(
        string $reservationCode,
        ?int $reservationId = null,
        int $attempt = 1,
    ): void {
        $this->gatewayForOnlinePurchase($reservationId, __FUNCTION__, $attempt)
            ->lockBooking($reservationCode);
    }

    public function confirm(
        string $reservationCode,
        ?int $reservationId = null,
        int $attempt = 1,
    ): array {
        $gateway = $this->gatewayForOnlinePurchase($reservationId, __FUNCTION__, $attempt);
        $gateway->confirmBooking($reservationCode);

        // SnappTrip requires checking booking state after confirm. The GET result,
        // rather than the confirm HTTP status alone, is authoritative here.
        return $gateway->booking($reservationCode);
    }

    public function status(
        string $reservationCode,
        ?int $reservationId = null,
        int $attempt = 1,
    ): array {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);

        return $this->gateway($provider, $reservationId, __FUNCTION__, $attempt)
            ->booking($reservationCode);
    }

    private function gatewayForOnlinePurchase(
        ?int $reservationId,
        string $method,
        int $attempt,
    ): SnappTripGateway {
        $provider = $this->providerForOnlinePurchase();

        return $this->gateway($provider, $reservationId, $method, $attempt);
    }

    private function gateway(
        Provider $provider,
        ?int $reservationId,
        string $method,
        int $attempt,
    ): SnappTripGateway {
        return $this->gateways
            ->make($provider)
            ->withRequestLogContext(
                $reservationId,
                self::class,
                $method,
                max(1, $attempt),
                true,
            );
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
