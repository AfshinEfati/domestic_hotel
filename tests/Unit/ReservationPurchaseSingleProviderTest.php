<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\ReservationPurchaseRequestService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

class ReservationPurchaseSingleProviderTest extends TestCase
{
    public function test_rooms_from_same_provider_resolve_to_one_provider(): void
    {
        $providerId = $this->singleProviderId(new Collection([
            $this->room(11, 7),
            $this->room(12, 7),
        ]));

        $this->assertSame(7, $providerId);
    }

    public function test_rooms_from_different_providers_are_rejected_before_purchase_creation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reservation final rooms must belong to exactly one provider.');

        $this->singleProviderId(new Collection([
            $this->room(11, 7),
            $this->room(12, 9),
        ]));
    }

    public function test_room_without_validated_provider_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reservation room 12 has no validated provider.');

        $this->singleProviderId(new Collection([
            $this->room(11, 7),
            $this->room(12, null),
        ]));
    }

    public function test_quoted_amount_uses_all_final_rooms_of_selected_provider(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'quotedAmount');
        $method->setAccessible(true);

        $amount = $method->invoke($service, new Collection([
            $this->room(11, 7, 100_000, 120_000),
            $this->room(12, 7, 80_000, null),
        ]));

        $this->assertSame(200_000, $amount);
    }

    private function singleProviderId(Collection $rooms): int
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'singleProviderId');
        $method->setAccessible(true);

        return $method->invoke($service, $rooms);
    }

    private function service(): ReservationPurchaseRequestService
    {
        return new ReservationPurchaseRequestService(
            $this->createMock(ReservationRepositoryInterface::class),
            $this->createMock(ReservationPurchaseRepositoryInterface::class),
            $this->createMock(ReservationPurchaseSegmentRepositoryInterface::class),
            $this->createMock(ReservationManualPurchaseRepositoryInterface::class),
            $this->createMock(ReservationManualReasonRepositoryInterface::class),
            $this->createMock(ProviderRepositoryInterface::class),
            $this->createMock(PurchaseResolverInterface::class),
            new HotelProviderRegistry(),
            $this->createMock(Container::class),
        );
    }

    private function room(
        int $id,
        ?int $providerId,
        ?int $initialPrice = null,
        ?int $validatedPrice = null,
    ): stdClass {
        $room = new stdClass();
        $room->id = $id;
        $room->provider_id = $providerId;
        $room->initial_price = $initialPrice;
        $room->validated_price = $validatedPrice;

        return $room;
    }
}
