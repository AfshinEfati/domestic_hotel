<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\ProviderCreditBalance;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderCreditBalanceRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\PurchaseResolver;
use App\Support\Provider\ProviderType;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PurchaseResolverFreshPreflightTest extends TestCase
{
    public function test_provider_with_fresh_preflight_is_not_blocked_by_missing_local_credit_snapshot(): void
    {
        $reservation = $this->reservation();
        $provider = $this->provider('snapptrip');

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(77)
            ->willReturn($reservation);

        $providers = $this->createMock(ProviderRepositoryInterface::class);
        $providers
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($provider);

        $maps = $this->createMock(AccommodationProviderMapRepositoryInterface::class);
        $maps
            ->expects($this->once())
            ->method('existsForAccommodationAndProvider')
            ->with(9, 5)
            ->willReturn(true);

        $manualRules = $this->createMock(PurchaseManualRuleRepositoryInterface::class);
        $manualRules
            ->expects($this->once())
            ->method('findMatching')
            ->willReturn(null);

        $credit = $this->createMock(ProviderCreditBalanceRepositoryInterface::class);
        $credit
            ->expects($this->never())
            ->method('findByProviderId');

        $registry = new HotelProviderRegistry();
        $registry->registerOnlinePurchasePreflightHandler(
            'snapptrip',
            FreshCreditPreflightHandler::class,
        );

        $resolver = new PurchaseResolver(
            $reservations,
            $providers,
            $maps,
            $manualRules,
            $credit,
            $this->createMock(ReservationManualReasonRepositoryInterface::class),
            $registry,
        );

        $result = $resolver->resolve(77, 5, 900_000);

        $this->assertSame(PurchaseMethod::ONLINE, $result->purchaseMode);
        $this->assertNull($result->manualReason);
        $this->assertSame(77, $result->reservationId);
        $this->assertSame(88, $result->reservationHotelId);
    }

    public function test_legacy_provider_without_preflight_keeps_snapshot_credit_safety_gate(): void
    {
        $reservation = $this->reservation();
        $provider = $this->provider('legacy-online');

        $reservations = $this->createMock(ReservationRepositoryInterface::class);
        $reservations
            ->method('findWithDetails')
            ->willReturn($reservation);

        $providers = $this->createMock(ProviderRepositoryInterface::class);
        $providers
            ->method('find')
            ->willReturn($provider);

        $maps = $this->createMock(AccommodationProviderMapRepositoryInterface::class);
        $maps
            ->method('existsForAccommodationAndProvider')
            ->willReturn(true);

        $manualRules = $this->createMock(PurchaseManualRuleRepositoryInterface::class);
        $manualRules
            ->method('findMatching')
            ->willReturn(null);

        $storedCredit = new ProviderCreditBalance();
        $storedCredit->forceFill([
            'provider_id' => 5,
            'balance' => 100_000,
            'synced_at' => now(),
        ]);

        $credit = $this->createMock(ProviderCreditBalanceRepositoryInterface::class);
        $credit
            ->expects($this->once())
            ->method('findByProviderId')
            ->with(5)
            ->willReturn($storedCredit);

        $resolver = new PurchaseResolver(
            $reservations,
            $providers,
            $maps,
            $manualRules,
            $credit,
            $this->createMock(ReservationManualReasonRepositoryInterface::class),
            new HotelProviderRegistry(),
        );

        $result = $resolver->resolve(77, 5, 900_000);

        $this->assertSame(PurchaseMethod::OFFLINE, $result->purchaseMode);
        $this->assertSame(PurchaseManualReason::INSUFFICIENT_CREDIT, $result->manualReason);
    }

    private function reservation(): Reservation
    {
        $hotel = new ReservationHotel();
        $hotel->forceFill([
            'id' => 88,
            'reservation_id' => 77,
            'accommodation_id' => 9,
            'is_final' => true,
        ]);

        $reservation = new Reservation();
        $reservation->forceFill([
            'id' => 77,
            'sale_amount' => 900_000,
        ]);
        $reservation->setRelation('hotels', new EloquentCollection([$hotel]));

        return $reservation;
    }

    private function provider(string $code): Provider
    {
        $provider = new Provider();
        $provider->forceFill([
            'id' => 5,
            'code' => $code,
            'provider_type' => ProviderType::INTEGRATION,
            'accommodation_id' => null,
            'is_active' => true,
            'is_online' => true,
            'config' => [
                'purchase' => [
                    'online_enabled' => true,
                ],
            ],
        ]);

        return $provider;
    }
}

final class FreshCreditPreflightHandler implements OnlinePurchasePreflightHandler
{
    /** @param Collection<int, \App\Models\ReservationPurchase> $purchases */
    public function check(Collection $purchases): OnlinePurchasePreflightResult
    {
        return OnlinePurchasePreflightResult::allowed();
    }
}
