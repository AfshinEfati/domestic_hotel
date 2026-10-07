<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Repositories\HotelProviderRefreshStateRepository;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Models\HotelProviderRefreshState;

class ProviderRefreshCoordinator
{
    public function __construct(
        private readonly HotelProviderRefreshStateRepository $states,
        private readonly HotelPriceRefreshScheduleRepository $schedules,
    ) {
    }

    public function begin(int $stateId, string $cycleKey): ?HotelProviderRefreshState
    {
        $state = $this->states->start($stateId, $cycleKey);
        if ($state === null) {
            return null;
        }

        if (!$this->schedules->isCurrentCycle(
            (int) $state->shared_schedule_id,
            (int) $state->accommodation_id,
            $this->sourceDueAt($state),
        )) {
            $this->states->markAttempted(
                $stateId,
                $cycleKey,
                ProviderRefreshOutcome::CYCLE_SUPERSEDED,
            );
            return null;
        }

        return $state;
    }

    public function done(
        int $stateId,
        string $cycleKey,
        string $outcome = ProviderRefreshOutcome::SUCCESS,
    ): void {
        $state = $this->states->markDone($stateId, $cycleKey, $outcome);
        if ($state !== null) {
            $this->finalize($state);
        }
    }

    public function attempted(int $stateId, string $cycleKey, string $outcome): void
    {
        $state = $this->states->markAttempted($stateId, $cycleKey, $outcome);
        if ($state !== null) {
            $this->finalize($state);
        }
    }

    public function retry(
        int $stateId,
        string $cycleKey,
        string $outcome,
        int $delaySeconds,
    ): void {
        $this->states->markRetry($stateId, $cycleKey, $outcome, $delaySeconds);
    }

    public function finalize(HotelProviderRefreshState $state): bool
    {
        if ($this->states->hasOpenState((string) $state->cycle_key)) {
            return false;
        }

        return $this->schedules->markCycleCompleted(
            (int) $state->shared_schedule_id,
            (int) $state->accommodation_id,
            $this->sourceDueAt($state),
        );
    }

    public function finalizeCycle(
        int $scheduleId,
        int $accommodationId,
        ?string $sourceDueAt,
        string $cycleKey,
    ): bool {
        if ($this->states->countForCycle($cycleKey) > 0 && $this->states->hasOpenState($cycleKey)) {
            return false;
        }

        return $this->schedules->markCycleCompleted($scheduleId, $accommodationId, $sourceDueAt);
    }

    private function sourceDueAt(HotelProviderRefreshState $state): ?string
    {
        return $state->source_due_at?->format('Y-m-d H:i:s');
    }
}
