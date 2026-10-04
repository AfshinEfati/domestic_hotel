<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\ProviderCancellation;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripOperationsRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use RuntimeException;

final class SnappTripCancellationService
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripOperationsRepository $operations,
    ) {
    }

    public function rules(
        int|string $providerPropertyId,
        int|string $providerRoomId,
        string $checkIn,
        string $checkOut,
    ): array {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);

        return $this->gateways->make($provider)->cancellationRules(
            $providerPropertyId,
            $providerRoomId,
            $checkIn,
            $checkOut,
        );
    }

    public function create(string $trackingCode, ?int $reservationPurchaseId = null): ProviderCancellation
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $gateway = $this->gateways->make($provider);
        $created = $gateway->createCancellation($trackingCode);
        $this->operations->saveCancellationCreated($provider, $trackingCode, $created, $reservationPurchaseId);

        // SnappTrip explicitly recommends an inquiry immediately after create because
        // automatic cancellation can already have reached ACCEPTED or PAID.
        return $this->inquire($trackingCode);
    }

    public function inquire(string $trackingCode): ProviderCancellation
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $payload = $this->gateways->make($provider)->cancellationInquiry($trackingCode);

        return $this->operations->saveCancellationInquiry($provider, $trackingCode, $payload);
    }

    public function accept(string $trackingCode): ProviderCancellation
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $current = $this->inquire($trackingCode);
        $status = strtoupper((string) $current->status);

        if (in_array($status, ['ACCEPTED', 'PAID'], true)) {
            return $current;
        }
        if ($status !== 'CALCULATED') {
            throw new RuntimeException("SnappTrip cancellation cannot be accepted while status is {$status}.");
        }

        $this->gateways->make($provider)->acceptCancellation($trackingCode);
        $this->operations->markCancellationDecision($provider, $trackingCode, 'ACCEPTED');

        return $this->inquire($trackingCode);
    }

    public function reject(string $trackingCode): ProviderCancellation
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $current = $this->inquire($trackingCode);
        $status = strtoupper((string) $current->status);

        if ($status === 'REJECTED') {
            return $current;
        }
        if ($status !== 'CALCULATED') {
            throw new RuntimeException("SnappTrip cancellation cannot be rejected while status is {$status}.");
        }

        $this->gateways->make($provider)->rejectCancellation($trackingCode);
        $this->operations->markCancellationDecision($provider, $trackingCode, 'REJECTED');

        return $this->inquire($trackingCode);
    }

    public function syncPending(int $limit = 100): int
    {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        $pending = $this->operations->pendingCancellations($provider, $limit);
        $count = 0;

        foreach ($pending as $cancellation) {
            $this->inquire((string) $cancellation->tracking_code);
            $count++;
        }

        return $count;
    }
}
