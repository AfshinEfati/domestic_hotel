<?php

namespace Tests\Unit;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Models\AccommodationProviderMap;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Tests\TestCase;

class ProviderPriceRefreshSchedulerTest extends TestCase
{
    public function test_one_due_hotel_fans_out_to_every_active_mapped_provider(): void
    {
        $slowProvider = (new Provider())->forceFill([
            'id' => 1,
            'code' => 'slow',
            'is_active' => true,
            'is_online' => false,
        ]);
        $fastProvider = (new Provider())->forceFill([
            'id' => 2,
            'code' => 'fast',
            'is_active' => true,
            'is_online' => true,
        ]);

        $providers = $this->createMock(ProviderRepositoryInterface::class);
        $providers->method('getAll')->willReturn([$slowProvider, $fastProvider]);

        $slowMap = (new AccommodationProviderMap())->forceFill([
            'id' => 11,
            'provider_id' => 1,
            'accommodation_id' => 100,
            'provider_property_id' => 'slow-100',
            'is_disabled' => false,
        ]);
        $fastMap = (new AccommodationProviderMap())->forceFill([
            'id' => 12,
            'provider_id' => 2,
            'accommodation_id' => 100,
            'provider_property_id' => 'fast-100',
            'is_disabled' => false,
        ]);

        $maps = $this->createMock(AccommodationProviderMapRepositoryInterface::class);
        $maps->expects($this->once())
            ->method('activeForAccommodation')
            ->with(100)
            ->willReturn(collect([$slowMap, $fastMap]));

        $schedule = (new HotelPriceRefreshSchedule())->forceFill([
            'id' => 77,
            'gds_id' => 100,
        ]);
        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->once())->method('assertReady');
        $schedules->expects($this->once())
            ->method('due')
            ->with(10)
            ->willReturn(collect([$schedule]));

        $slow = new SlowTestPriceRefreshHandler();
        $fast = new FastTestPriceRefreshHandler();
        $this->app->instance(SlowTestPriceRefreshHandler::class, $slow);
        $this->app->instance(FastTestPriceRefreshHandler::class, $fast);

        $registry = new HotelProviderRegistry();
        $registry->registerPriceRefreshHandler('slow', SlowTestPriceRefreshHandler::class);
        $registry->registerPriceRefreshHandler('fast', FastTestPriceRefreshHandler::class);

        $scheduler = new ProviderPriceRefreshScheduler($providers, $maps, $schedules, $registry);

        $this->assertSame(2, $scheduler->dispatch());
        $this->assertSame([[1, 11, 77, 100]], $slow->dispatched);
        $this->assertSame([[2, 12, 77, 100]], $fast->dispatched);
    }

    public function test_outbound_guard_uses_active_state_not_online_procurement_mode(): void
    {
        $provider = (new Provider())->forceFill([
            'id' => 9,
            'code' => 'snap',
            'is_active' => true,
            'is_online' => false,
        ]);

        $repository = $this->createMock(ProviderRepositoryInterface::class);
        $guard = new ProviderOutboundGuard($repository);

        $this->assertTrue($guard->allows($provider));

        $provider->is_active = false;
        $this->assertFalse($guard->allows($provider));
    }
}

final class SlowTestPriceRefreshHandler implements PriceRefreshSchedulerHandler
{
    /** @var array<int,array{int,int,int,int}> */
    public array $dispatched = [];

    public function enabled(Provider $provider): bool
    {
        return $provider->is_active === true;
    }

    public function hotelCapacityPerMinute(Provider $provider): int
    {
        return 10;
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $scheduleId,
        int $accommodationId,
    ): bool {
        $this->dispatched[] = [(int) $provider->id, (int) $map->id, $scheduleId, $accommodationId];

        return true;
    }
}

final class FastTestPriceRefreshHandler implements PriceRefreshSchedulerHandler
{
    /** @var array<int,array{int,int,int,int}> */
    public array $dispatched = [];

    public function enabled(Provider $provider): bool
    {
        return $provider->is_active === true;
    }

    public function hotelCapacityPerMinute(Provider $provider): int
    {
        return 60;
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $scheduleId,
        int $accommodationId,
    ): bool {
        $this->dispatched[] = [(int) $provider->id, (int) $map->id, $scheduleId, $accommodationId];

        return true;
    }
}
