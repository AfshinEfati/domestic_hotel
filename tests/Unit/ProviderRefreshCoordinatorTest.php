<?php

namespace Tests\Unit;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Repositories\HotelProviderRefreshStateRepository;
use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Models\HotelProviderRefreshState;
use Tests\TestCase;

class ProviderRefreshCoordinatorTest extends TestCase
{
    public function test_shared_cycle_advances_only_after_all_provider_states_are_terminal(): void
    {
        $first = (new HotelProviderRefreshState())->forceFill([
            'id' => 1,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'cycle_key' => 'cycle-1',
            'source_due_at' => '2026-10-07 10:00:00',
        ]);
        $second = (new HotelProviderRefreshState())->forceFill([
            'id' => 2,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'cycle_key' => 'cycle-1',
            'source_due_at' => '2026-10-07 10:00:00',
        ]);

        $states = $this->createMock(HotelProviderRefreshStateRepository::class);
        $states->expects($this->once())
            ->method('markDone')
            ->with(1, ProviderRefreshOutcome::SUCCESS)
            ->willReturn($first);
        $states->expects($this->once())
            ->method('markAttempted')
            ->with(2, ProviderRefreshOutcome::PROVIDER_404)
            ->willReturn($second);
        $states->expects($this->exactly(2))
            ->method('hasOpenState')
            ->with('cycle-1')
            ->willReturnOnConsecutiveCalls(true, false);

        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->once())
            ->method('markCycleCompleted')
            ->with(77, 100, '2026-10-07 10:00:00')
            ->willReturn(true);

        $coordinator = new ProviderRefreshCoordinator($states, $schedules);
        $coordinator->done(1);
        $coordinator->attempted(2, ProviderRefreshOutcome::PROVIDER_404);
    }

    public function test_retryable_failure_never_advances_shared_cycle(): void
    {
        $states = $this->createMock(HotelProviderRefreshStateRepository::class);
        $states->expects($this->once())
            ->method('markRetry')
            ->with(5, ProviderRefreshOutcome::RATE_LIMITED, 120);

        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->never())->method('markCycleCompleted');

        $coordinator = new ProviderRefreshCoordinator($states, $schedules);
        $coordinator->retry(5, ProviderRefreshOutcome::RATE_LIMITED, 120);
    }

    public function test_stale_worker_is_terminated_without_touching_new_shared_cycle(): void
    {
        $state = (new HotelProviderRefreshState())->forceFill([
            'id' => 9,
            'shared_schedule_id' => 77,
            'accommodation_id' => 100,
            'cycle_key' => 'old-cycle',
            'source_due_at' => '2026-10-07 10:00:00',
        ]);

        $states = $this->createMock(HotelProviderRefreshStateRepository::class);
        $states->expects($this->once())
            ->method('start')
            ->with(9, 'old-cycle')
            ->willReturn($state);
        $states->expects($this->once())
            ->method('markAttempted')
            ->with(9, ProviderRefreshOutcome::CYCLE_SUPERSEDED);

        $schedules = $this->createMock(HotelPriceRefreshScheduleRepository::class);
        $schedules->expects($this->once())
            ->method('isCurrentCycle')
            ->with(77, 100, '2026-10-07 10:00:00')
            ->willReturn(false);
        $schedules->expects($this->never())->method('markCycleCompleted');

        $coordinator = new ProviderRefreshCoordinator($states, $schedules);

        $this->assertNull($coordinator->begin(9, 'old-cycle'));
    }
}
