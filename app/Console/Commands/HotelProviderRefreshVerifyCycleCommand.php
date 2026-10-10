<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository;
use App\Domain\Hotel\Repositories\HotelProviderRefreshStateRepository;
use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Domain\Hotel\Support\ProviderRefreshStateStatus;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\HotelProviderRefreshState;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class HotelProviderRefreshVerifyCycleCommand extends Command
{
    protected $signature = 'hotel:provider-refresh-verify-cycle
        {--hotel= : Optional accommodation id; otherwise prefer a current cycle with multiple providers}';

    protected $description = 'Verify provider-cycle terminal/retry gating against local copied shared data; all changes are rolled back and no HTTP is sent.';

    public function handle(
        HotelProviderRefreshStateRepository $states,
        HotelPriceRefreshScheduleRepository $schedules,
        ProviderRefreshCoordinator $coordinator,
    ): int {
        if (app()->isProduction()) {
            $this->error('This verification command is disabled in production.');
            return self::FAILURE;
        }

        $schedules->assertReady();
        $selection = $this->selectCurrentCycle($states, $schedules);
        if ($selection === null) {
            $this->error('No current due hotel with synchronized local provider states was found. Run --sync-only first.');
            return self::FAILURE;
        }

        [$schedule, $cycleStates] = $selection;
        $beforeDueAt = $this->sharedDueAt((int) $schedule->id);
        $providerCodes = $cycleStates
            ->load('provider:id,code')
            ->map(fn (HotelProviderRefreshState $state): string => (string) ($state->provider?->code ?? $state->provider_id))
            ->implode(', ');

        $this->info('Selected hotel: '.(int) $schedule->gds_id);
        $this->info('Shared schedule id: '.(int) $schedule->id);
        $this->info('Current due at: '.($beforeDueAt ?? 'NULL'));
        $this->info('Current-cycle providers: '.$providerCodes);

        try {
            $this->verifyTerminalGating($schedule, $cycleStates, $beforeDueAt, $coordinator);
            $this->verifyRetryGating($schedule, $cycleStates, $beforeDueAt, $coordinator);
        } catch (Throwable $exception) {
            $this->error('Verification failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $afterRollback = $this->sharedDueAt((int) $schedule->id);
        if ($afterRollback !== $beforeDueAt) {
            $this->error('Rollback verification failed: shared due time changed persistently.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('PASS: terminal results advance the shared cycle only after every provider is terminal.');
        $this->info('PASS: retry keeps the shared cycle open.');
        $this->info('PASS: all local/shared verification writes were rolled back.');
        $this->info('No provider HTTP requests were sent.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: HotelPriceRefreshSchedule, 1: Collection<int, HotelProviderRefreshState>}|null
     */
    private function selectCurrentCycle(
        HotelProviderRefreshStateRepository $states,
        HotelPriceRefreshScheduleRepository $schedules,
    ): ?array {
        $hotelOption = trim((string) $this->option('hotel'));
        if ($hotelOption !== '') {
            if (!ctype_digit($hotelOption) || (int) $hotelOption <= 0) {
                throw new RuntimeException('The --hotel option must be a positive accommodation id.');
            }

            $schedule = $this->dueScheduleForHotel((int) $hotelOption);
            if ($schedule === null) {
                return null;
            }

            $cycleKey = $states->cycleKey(
                (int) $schedule->id,
                (int) $schedule->gds_id,
                $schedule->next_gds_run_at?->format('Y-m-d H:i:s'),
            );
            $cycleStates = HotelProviderRefreshState::query()
                ->where('cycle_key', $cycleKey)
                ->orderBy('provider_id')
                ->get();

            return $cycleStates->isEmpty() ? null : [$schedule, $cycleStates];
        }

        $groups = HotelProviderRefreshState::query()
            ->select(['shared_schedule_id', 'accommodation_id', 'cycle_key', 'source_due_at'])
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('shared_schedule_id', 'accommodation_id', 'cycle_key', 'source_due_at')
            ->orderByDesc('aggregate')
            ->orderBy('shared_schedule_id')
            ->get();

        foreach ($groups as $group) {
            $sourceDueAt = $group->source_due_at === null
                ? null
                : CarbonImmutable::parse($group->source_due_at)->format('Y-m-d H:i:s');

            if (!$schedules->isCurrentCycle(
                (int) $group->shared_schedule_id,
                (int) $group->accommodation_id,
                $sourceDueAt,
            )) {
                continue;
            }

            $schedule = $this->dueScheduleForHotel((int) $group->accommodation_id, (int) $group->shared_schedule_id);
            if ($schedule === null) {
                continue;
            }

            $cycleStates = HotelProviderRefreshState::query()
                ->where('cycle_key', (string) $group->cycle_key)
                ->orderBy('provider_id')
                ->get();

            if ($cycleStates->isNotEmpty()) {
                return [$schedule, $cycleStates];
            }
        }

        return null;
    }

    private function dueScheduleForHotel(int $accommodationId, ?int $scheduleId = null): ?HotelPriceRefreshSchedule
    {
        $query = HotelPriceRefreshSchedule::query()
            ->where('gds_id', $accommodationId)
            ->where('is_active', true)
            ->where(function ($due): void {
                $due->whereNull('next_gds_run_at')
                    ->orWhereRaw('next_gds_run_at <= CURRENT_TIMESTAMP');
            });

        if ($scheduleId !== null) {
            $query->whereKey($scheduleId);
        }

        return $query->orderBy('id')->first();
    }

    /** @param Collection<int, HotelProviderRefreshState> $cycleStates */
    private function verifyTerminalGating(
        HotelPriceRefreshSchedule $schedule,
        Collection $cycleStates,
        ?string $beforeDueAt,
        ProviderRefreshCoordinator $coordinator,
    ): void {
        $local = DB::connection();
        $shared = DB::connection('shared_ssp');
        $this->beginBoth($local, $shared);

        try {
            $count = $cycleStates->count();
            foreach ($cycleStates->values() as $index => $original) {
                $state = $this->queueAndBegin($original, $coordinator);
                $last = $index === $count - 1;

                if ($index === 0) {
                    $coordinator->done((int) $state->id, (string) $state->cycle_key);
                } else {
                    $coordinator->attempted(
                        (int) $state->id,
                        (string) $state->cycle_key,
                        ProviderRefreshOutcome::PROVIDER_HTTP_ERROR,
                    );
                }

                $currentDueAt = $this->sharedDueAt((int) $schedule->id);
                if (!$last && $currentDueAt !== $beforeDueAt) {
                    throw new RuntimeException('Shared cycle advanced before all provider states were terminal.');
                }
                if ($last && $currentDueAt === $beforeDueAt) {
                    throw new RuntimeException('Shared cycle did not advance after all provider states became terminal.');
                }
            }
        } finally {
            $this->rollbackBoth($local, $shared);
        }
    }

    /** @param Collection<int, HotelProviderRefreshState> $cycleStates */
    private function verifyRetryGating(
        HotelPriceRefreshSchedule $schedule,
        Collection $cycleStates,
        ?string $beforeDueAt,
        ProviderRefreshCoordinator $coordinator,
    ): void {
        $local = DB::connection();
        $shared = DB::connection('shared_ssp');
        $this->beginBoth($local, $shared);

        try {
            $lastIndex = $cycleStates->count() - 1;
            foreach ($cycleStates->values() as $index => $original) {
                $state = $this->queueAndBegin($original, $coordinator);

                if ($index === $lastIndex) {
                    $coordinator->retry(
                        (int) $state->id,
                        (string) $state->cycle_key,
                        ProviderRefreshOutcome::RATE_LIMITED,
                        60,
                    );
                } else {
                    $coordinator->done((int) $state->id, (string) $state->cycle_key);
                }
            }

            if ($this->sharedDueAt((int) $schedule->id) !== $beforeDueAt) {
                throw new RuntimeException('Shared cycle advanced while a provider state was retryable.');
            }
        } finally {
            $this->rollbackBoth($local, $shared);
        }
    }

    private function queueAndBegin(
        HotelProviderRefreshState $original,
        ProviderRefreshCoordinator $coordinator,
    ): HotelProviderRefreshState {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey((int) $original->id)
            ->where('cycle_key', (string) $original->cycle_key)
            ->update([
                'status' => ProviderRefreshStateStatus::QUEUED,
                'outcome' => null,
                'queued_at' => $now,
                'started_at' => null,
                'next_attempt_at' => null,
                'completed_at' => null,
                'lease_expires_at' => $now->addMinutes(5),
                'updated_at' => $now,
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Unable to stage local provider state for verification.');
        }

        $state = $coordinator->begin((int) $original->id, (string) $original->cycle_key);
        if ($state === null) {
            throw new RuntimeException('Coordinator rejected the current provider cycle during verification.');
        }

        return $state;
    }

    private function sharedDueAt(int $scheduleId): ?string
    {
        $value = DB::connection('shared_ssp')
            ->table('hotel_price_refresh_schedules')
            ->where('id', $scheduleId)
            ->value('next_gds_run_at');

        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->format('Y-m-d H:i:s');
    }

    private function beginBoth(ConnectionInterface $local, ConnectionInterface $shared): void
    {
        $local->beginTransaction();
        try {
            $shared->beginTransaction();
        } catch (Throwable $exception) {
            $local->rollBack();
            throw $exception;
        }
    }

    private function rollbackBoth(ConnectionInterface $local, ConnectionInterface $shared): void
    {
        if ($shared->transactionLevel() > 0) {
            $shared->rollBack();
        }
        if ($local->transactionLevel() > 0) {
            $local->rollBack();
        }
    }
}
