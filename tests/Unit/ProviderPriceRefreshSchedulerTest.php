<?php

namespace Tests\Unit;

use App\Domain\Hotel\Contracts\PriceRefreshSchedulerHandler;
use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Repositories\HotelProviderRefreshStateRepository;
use App\Domain\Hotel\Services\GrsScheduledPriceRefresh;
use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Models\AccommodationProviderMap;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\HotelProviderRefreshState;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripScheduledPriceRefresh;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Tests\TestCase;

class ProviderPriceRefreshSchedulerTest extends TestCase
{
    public function test_one_due_hotel_fans_out_with_each_provider_own_capacity(): void
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
        $slowDuplicateMap = (new AccommodationProviderMap())->forceFill([
            'id' => 13,
            'provider_id' => 1,
            'accommodation_id' => 100,
            'provider_property_id' => 'slow-100-duplicate',
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
            ->method('forAccommodations')
            ->with([100])
            ->willReturn(collect([$slowDuplicateMap, $fastMap, $slowMap]));
        $maps->method('find')->willReturnMap([
            [11, $slowMap],
            [12, $fastMap],
        ]);

        $schedule = (new HotelPriceRefreshSchedule())->forceFill([
            'id' => 77,
            'gds_id' => 100,
            'next_gds_run_at' => null,
        ]);
        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->once())->method('assertReady');
        $schedules->expects($this->once())
            ->method('due')
            ->with(10000)
            ->willReturn(collect([$schedule]));
        $schedules->expects($this->exactly(2))
            ->method('isCurrentCycle')
            ->with(77, 100, null)
            ->willReturn(true);

        $slowState = (new HotelProviderRefreshState())->forceFill([
            'id' => 101,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'provider_id' => 1,
            'accommodation_provider_map_id' => 11,
            'cycle_key' => 'cycle',
            'source_due_at' => null,
        ]);
        $fastState = (new HotelProviderRefreshState())->forceFill([
            'id' => 102,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'provider_id' => 2,
            'accommodation_provider_map_id' => 12,
            'cycle_key' => 'cycle',
            'source_due_at' => null,
        ]);

        $states = $this->createMock(HotelProviderRefreshStateRepository::class);
        $states->method('cycleKey')->willReturn('cycle');
        $states->expects($this->exactly(2))->method('ensure');
        $states->expects($this->once())->method('hasOpenState')->with('cycle')->willReturn(true);
        $states->expects($this->exactly(2))
            ->method('claimForProvider')
            ->willReturnCallback(static fn (int $providerId, int $capacity) => match ($providerId) {
                1 => tap(collect([$slowState]), static function () use ($capacity): void {
                    self::assertSame(10, $capacity);
                }),
                2 => tap(collect([$fastState]), static function () use ($capacity): void {
                    self::assertSame(60, $capacity);
                }),
            });

        $coordinator = $this->createMock(ProviderRefreshCoordinator::class);

        $slow = new SlowTestPriceRefreshHandler();
        $fast = new FastTestPriceRefreshHandler();
        $this->app->instance(SlowTestPriceRefreshHandler::class, $slow);
        $this->app->instance(FastTestPriceRefreshHandler::class, $fast);

        $registry = new HotelProviderRegistry();
        $registry->registerPriceRefreshHandler('slow', SlowTestPriceRefreshHandler::class);
        $registry->registerPriceRefreshHandler('fast', FastTestPriceRefreshHandler::class);

        $scheduler = new ProviderPriceRefreshScheduler(
            $providers,
            $maps,
            $schedules,
            $states,
            $coordinator,
            $registry,
        );

        $this->assertSame(2, $scheduler->dispatch());
        $this->assertSame([[1, 11, 101, 'cycle', 77, 100]], $slow->dispatched);
        $this->assertSame([[2, 12, 102, 'cycle', 77, 100]], $fast->dispatched);
    }

    public function test_targeted_dispatch_materializes_all_provider_states_but_queues_only_selected_provider(): void
    {
        $slowProvider = (new Provider())->forceFill([
            'id' => 1,
            'code' => 'slow',
            'is_active' => true,
        ]);
        $fastProvider = (new Provider())->forceFill([
            'id' => 2,
            'code' => 'fast',
            'is_active' => true,
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
            ->method('forAccommodations')
            ->with([100])
            ->willReturn(collect([$slowMap, $fastMap]));
        $maps->method('find')->with(12)->willReturn($fastMap);

        $schedule = (new HotelPriceRefreshSchedule())->forceFill([
            'id' => 77,
            'gds_id' => 100,
            'next_gds_run_at' => null,
        ]);
        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->once())->method('assertReady');
        $schedules->expects($this->once())
            ->method('dueForAccommodation')
            ->with(100)
            ->willReturn($schedule);
        $schedules->expects($this->once())
            ->method('isCurrentCycle')
            ->with(77, 100, null)
            ->willReturn(true);

        $slowState = (new HotelProviderRefreshState())->forceFill([
            'id' => 101,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'provider_id' => 1,
            'accommodation_provider_map_id' => 11,
            'cycle_key' => 'cycle',
            'source_due_at' => null,
        ]);
        $fastState = (new HotelProviderRefreshState())->forceFill([
            'id' => 102,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'provider_id' => 2,
            'accommodation_provider_map_id' => 12,
            'cycle_key' => 'cycle',
            'source_due_at' => null,
        ]);
        $states = $this->createMock(HotelProviderRefreshStateRepository::class);
        $states->method('cycleKey')->willReturn('cycle');
        $states->expects($this->exactly(2))
            ->method('ensure')
            ->willReturnCallback(static fn (
                int $scheduleId,
                int $accommodationId,
                Provider $provider,
            ) => (int) $provider->id === 1 ? $slowState : $fastState);
        $states->expects($this->once())->method('hasOpenState')->with('cycle')->willReturn(true);
        $states->expects($this->once())
            ->method('claimSpecific')
            ->with(102, 'cycle')
            ->willReturn($fastState);

        $coordinator = $this->createMock(ProviderRefreshCoordinator::class);
        $slow = new SlowTestPriceRefreshHandler();
        $fast = new FastTestPriceRefreshHandler();
        $this->app->instance(SlowTestPriceRefreshHandler::class, $slow);
        $this->app->instance(FastTestPriceRefreshHandler::class, $fast);
        $registry = new HotelProviderRegistry();
        $registry->registerPriceRefreshHandler('slow', SlowTestPriceRefreshHandler::class);
        $registry->registerPriceRefreshHandler('fast', FastTestPriceRefreshHandler::class);

        $scheduler = new ProviderPriceRefreshScheduler(
            $providers,
            $maps,
            $schedules,
            $states,
            $coordinator,
            $registry,
        );

        $this->assertSame(1, $scheduler->dispatchAccommodation(100, 'fast', 40));
        $this->assertSame([], $slow->dispatched);
        $this->assertSame([[2, 12, 102, 'cycle', 77, 100]], $fast->dispatched);
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

    public function test_snapptrip_capacity_accounts_for_calendar_chunk_request_cost(): void
    {
        $snap = (new Provider())->forceFill([
            'id' => 4,
            'code' => 'snap',
            'is_active' => true,
            'config' => [
                'rate_limit' => [
                    'max_requests' => 120,
                    'window_minutes' => 1,
                ],
                'price_refresh' => [
                    'default_days' => 90,
                ],
            ],
        ]);

        $handler = new SnappTripScheduledPriceRefresh();

        $this->assertSame(60, $handler->hotelCapacityPerMinute($snap, 40));
        $this->assertSame(30, $handler->hotelCapacityPerMinute($snap, 80));
        $this->assertSame(20, $handler->hotelCapacityPerMinute($snap, 90));
        $this->assertSame(20, $handler->hotelCapacityPerMinute($snap));
    }

    public function test_active_provider_participates_even_when_legacy_scheduler_flag_is_false(): void
    {
        $grs = (new Provider())->forceFill([
            'code' => 'grs',
            'is_active' => true,
            'config' => ['price_refresh' => ['scheduler_enabled' => false]],
        ]);
        $snap = (new Provider())->forceFill([
            'code' => 'snap',
            'is_active' => true,
            'config' => ['price_refresh' => ['scheduler_enabled' => false]],
        ]);

        $this->assertTrue((new GrsScheduledPriceRefresh())->enabled($grs));
        $this->assertTrue((new SnappTripScheduledPriceRefresh())->enabled($snap));

        $grs->is_active = false;
        $snap->is_active = false;

        $this->assertFalse((new GrsScheduledPriceRefresh())->enabled($grs));
        $this->assertFalse((new SnappTripScheduledPriceRefresh())->enabled($snap));
    }
}

final class SlowTestPriceRefreshHandler implements PriceRefreshSchedulerHandler
{
    /** @var array<int,array{int,int,int,string,int,int}> */
    public array $dispatched = [];

    public function enabled(Provider $provider): bool
    {
        return $provider->is_active === true;
    }

    public function hotelCapacityPerMinute(Provider $provider, ?int $days = null): int
    {
        return 10;
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $refreshStateId,
        string $cycleKey,
        int $scheduleId,
        int $accommodationId,
        ?int $days = null,
    ): bool {
        $this->dispatched[] = [
            (int) $provider->id,
            (int) $map->id,
            $refreshStateId,
            $cycleKey,
            $scheduleId,
            $accommodationId,
        ];

        return true;
    }
}

final class FastTestPriceRefreshHandler implements PriceRefreshSchedulerHandler
{
    /** @var array<int,array{int,int,int,string,int,int}> */
    public array $dispatched = [];

    public function enabled(Provider $provider): bool
    {
        return $provider->is_active === true;
    }

    public function hotelCapacityPerMinute(Provider $provider, ?int $days = null): int
    {
        return 60;
    }

    public function dispatch(
        Provider $provider,
        AccommodationProviderMap $map,
        int $refreshStateId,
        string $cycleKey,
        int $scheduleId,
        int $accommodationId,
        ?int $days = null,
    ): bool {
        $this->dispatched[] = [
            (int) $provider->id,
            (int) $map->id,
            $refreshStateId,
            $cycleKey,
            $scheduleId,
            $accommodationId,
        ];

        return true;
    }
}
