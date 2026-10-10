<?php

namespace App\Domain\Hotel\Repositories;

use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Domain\Hotel\Support\ProviderRefreshStateStatus;
use App\Models\AccommodationProviderMap;
use App\Models\HotelProviderRefreshState;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HotelProviderRefreshStateRepository
{
    private const LEASE_SECONDS = 300;

    public function cycleKey(int $scheduleId, int $accommodationId, ?string $sourceDueAt): string
    {
        return hash('sha256', implode('|', [
            $scheduleId,
            $accommodationId,
            $sourceDueAt === null || trim($sourceDueAt) === '' ? 'initial' : trim($sourceDueAt),
        ]));
    }

    public function ensure(
        int $scheduleId,
        int $accommodationId,
        Provider $provider,
        AccommodationProviderMap $map,
        ?string $sourceDueAt,
        bool $dispatchable,
        ?string $terminalOutcome = null,
    ): HotelProviderRefreshState {
        $cycleKey = $this->cycleKey($scheduleId, $accommodationId, $sourceDueAt);
        $state = HotelProviderRefreshState::query()->firstOrCreate(
            [
                'accommodation_id' => $accommodationId,
                'provider_id' => (int) $provider->id,
            ],
            [
                'shared_schedule_id' => $scheduleId,
                'accommodation_provider_map_id' => (int) $map->id,
                'cycle_key' => $cycleKey,
                'source_due_at' => $sourceDueAt,
                'status' => $dispatchable
                    ? ProviderRefreshStateStatus::PENDING
                    : ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $dispatchable ? null : $terminalOutcome,
                'completed_at' => $dispatchable ? null : now(),
            ]
        );

        // A new shared due time opens a new cycle. Reuse the hotel/provider row and
        // reset only cycle-scoped execution fields; keep last_success_at as history.
        if ((string) $state->cycle_key !== $cycleKey) {
            $state->forceFill([
                'shared_schedule_id' => $scheduleId,
                'accommodation_provider_map_id' => (int) $map->id,
                'cycle_key' => $cycleKey,
                'source_due_at' => $sourceDueAt,
                'status' => $dispatchable
                    ? ProviderRefreshStateStatus::PENDING
                    : ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $dispatchable ? null : $terminalOutcome,
                'attempts' => 0,
                'next_attempt_at' => null,
                'queued_at' => null,
                'started_at' => null,
                'last_attempt_at' => null,
                'completed_at' => $dispatchable ? null : now(),
                'lease_expires_at' => null,
            ])->save();

            return $state->fresh();
        }

        if (in_array((int) $state->status, ProviderRefreshStateStatus::terminal(), true)) {
            // A safe sync may have observed a provider/map while it was disabled.
            // If the same shared cycle is still open and that condition is repaired,
            // reopen only configuration/mapping terminal states. Provider-side
            // outcomes such as 404/empty/timeout remain terminal for this cycle.
            if ($dispatchable && in_array((string) $state->outcome, [
                ProviderRefreshOutcome::SCHEDULER_DISABLED,
                ProviderRefreshOutcome::PROVIDER_DISABLED,
                ProviderRefreshOutcome::MAP_DISABLED,
                ProviderRefreshOutcome::MAP_UNAVAILABLE,
            ], true)) {
                $state->forceFill([
                    'shared_schedule_id' => $scheduleId,
                    'accommodation_provider_map_id' => (int) $map->id,
                    'status' => ProviderRefreshStateStatus::PENDING,
                    'outcome' => null,
                    'attempts' => 0,
                    'next_attempt_at' => null,
                    'queued_at' => null,
                    'started_at' => null,
                    'last_attempt_at' => null,
                    'completed_at' => null,
                    'lease_expires_at' => null,
                ])->save();

                return $state->fresh();
            }

            return $state;
        }

        $changes = [
            'shared_schedule_id' => $scheduleId,
            'accommodation_provider_map_id' => (int) $map->id,
        ];

        if (!$dispatchable) {
            $changes += [
                'status' => ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $terminalOutcome,
                'completed_at' => now(),
                'next_attempt_at' => null,
                'lease_expires_at' => null,
            ];
        }

        $state->forceFill($changes)->save();

        return $state->fresh();
    }

    /** @return Collection<int, HotelProviderRefreshState> */
    public function claimForProvider(int $providerId, int $limit): Collection
    {
        $limit = max(1, $limit);
        $now = CarbonImmutable::now();
        $candidates = HotelProviderRefreshState::query()
            ->where('provider_id', $providerId)
            ->where(function ($query) use ($now): void {
                $query->where(function ($claimable) use ($now): void {
                    $claimable
                        ->whereIn('status', ProviderRefreshStateStatus::claimable())
                        ->where(function ($retryAt) use ($now): void {
                            $retryAt->whereNull('next_attempt_at')
                                ->orWhere('next_attempt_at', '<=', $now);
                        });
                })->orWhere(function ($stale) use ($now): void {
                    $stale
                        ->whereIn('status', [
                            ProviderRefreshStateStatus::QUEUED,
                            ProviderRefreshStateStatus::PROCESSING,
                        ])
                        ->whereNotNull('lease_expires_at')
                        ->where('lease_expires_at', '<=', $now);
                });
            })
            ->orderByRaw('source_due_at IS NOT NULL')
            ->orderBy('source_due_at')
            ->orderBy('id')
            ->limit($limit * 3)
            ->get();

        $claimed = collect();
        foreach ($candidates as $candidate) {
            if ($claimed->count() >= $limit) {
                break;
            }

            $query = HotelProviderRefreshState::query()
                ->whereKey($candidate->id)
                ->where('cycle_key', (string) $candidate->cycle_key)
                ->where('status', (int) $candidate->status);

            if (in_array((int) $candidate->status, ProviderRefreshStateStatus::claimable(), true)) {
                $query->where(function ($retryAt) use ($now): void {
                    $retryAt->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', $now);
                });
            } else {
                $query->whereNotNull('lease_expires_at')
                    ->where('lease_expires_at', '<=', $now);
            }

            $updated = $query->update([
                'status' => ProviderRefreshStateStatus::QUEUED,
                'queued_at' => $now,
                'lease_expires_at' => $now->addSeconds(self::LEASE_SECONDS),
                'updated_at' => $now,
            ]);

            if ($updated === 1) {
                $claimed->push(HotelProviderRefreshState::query()->findOrFail($candidate->id));
            }
        }

        return $claimed;
    }

    public function start(int $stateId, string $cycleKey): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey($stateId)
            ->where('cycle_key', $cycleKey)
            ->where('status', ProviderRefreshStateStatus::QUEUED)
            ->update([
                'status' => ProviderRefreshStateStatus::PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'started_at' => $now,
                'last_attempt_at' => $now,
                'lease_expires_at' => $now->addSeconds(self::LEASE_SECONDS),
                'updated_at' => $now,
            ]);

        return $updated === 1
            ? HotelProviderRefreshState::query()->find($stateId)
            : null;
    }

    public function markDone(int $stateId, string $cycleKey, string $outcome): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey($stateId)
            ->where('cycle_key', $cycleKey)
            ->update([
                'status' => ProviderRefreshStateStatus::DONE,
                'outcome' => $outcome,
                'last_success_at' => $now,
                'completed_at' => $now,
                'next_attempt_at' => null,
                'lease_expires_at' => null,
                'updated_at' => $now,
            ]);

        return $updated === 1 ? HotelProviderRefreshState::query()->find($stateId) : null;
    }

    public function markAttempted(int $stateId, string $cycleKey, string $outcome): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey($stateId)
            ->where('cycle_key', $cycleKey)
            ->update([
                'status' => ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $outcome,
                'completed_at' => $now,
                'next_attempt_at' => null,
                'lease_expires_at' => null,
                'updated_at' => $now,
            ]);

        return $updated === 1 ? HotelProviderRefreshState::query()->find($stateId) : null;
    }

    public function markRetry(
        int $stateId,
        string $cycleKey,
        string $outcome,
        int $delaySeconds,
    ): ?HotelProviderRefreshState {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey($stateId)
            ->where('cycle_key', $cycleKey)
            ->update([
                'status' => ProviderRefreshStateStatus::RETRY,
                'outcome' => $outcome,
                'next_attempt_at' => $now->addSeconds(max(1, $delaySeconds)),
                'completed_at' => null,
                'lease_expires_at' => null,
                'updated_at' => $now,
            ]);

        return $updated === 1 ? HotelProviderRefreshState::query()->find($stateId) : null;
    }

    public function hasOpenState(string $cycleKey): bool
    {
        return HotelProviderRefreshState::query()
            ->where('cycle_key', $cycleKey)
            ->whereNotIn('status', ProviderRefreshStateStatus::terminal())
            ->exists();
    }

    public function countForCycle(string $cycleKey): int
    {
        return HotelProviderRefreshState::query()
            ->where('cycle_key', $cycleKey)
            ->count();
    }

    public function find(int $stateId): ?HotelProviderRefreshState
    {
        return HotelProviderRefreshState::query()->find($stateId);
    }
}
