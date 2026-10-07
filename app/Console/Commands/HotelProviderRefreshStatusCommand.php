<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Support\ProviderRefreshStateStatus;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\HotelProviderRefreshState;
use App\Models\Provider;
use Illuminate\Console\Command;

class HotelProviderRefreshStatusCommand extends Command
{
    protected $signature = 'hotel:provider-refresh-status {--provider= : Optional provider code} {--limit=20 : Recent local states to show}';

    protected $description = 'Show shared due hotels and local per-provider refresh state without using Tinker.';

    public function handle(): int
    {
        $providerCode = trim((string) $this->option('provider'));
        $provider = null;
        if ($providerCode !== '') {
            $provider = Provider::query()->where('code', $providerCode)->first();
            if ($provider === null) {
                $this->error("Provider [{$providerCode}] was not found.");
                return self::FAILURE;
            }
        }

        $sharedDue = HotelPriceRefreshSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('gds_id')
            ->where(function ($query): void {
                $query->whereNull('next_gds_run_at')
                    ->orWhereRaw('next_gds_run_at <= CURRENT_TIMESTAMP');
            })
            ->count();

        $base = HotelProviderRefreshState::query();
        if ($provider !== null) {
            $base->where('provider_id', (int) $provider->id);
        }

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                ProviderRefreshStateStatus::name((int) $row->status) => (int) $row->aggregate,
            ])
            ->all();

        $this->info('Shared due hotels: '.$sharedDue);
        $this->table(
            ['state', 'count'],
            collect($counts)->map(fn (int $count, string $state): array => [$state, $count])->values()->all(),
        );

        $limit = max(1, min(200, (int) $this->option('limit')));
        $recent = (clone $base)
            ->with('provider:id,code')
            ->latest('id')
            ->limit($limit)
            ->get();

        $this->table(
            ['id', 'hotel', 'provider', 'state', 'outcome', 'attempts', 'next attempt', 'completed'],
            $recent->map(fn (HotelProviderRefreshState $state): array => [
                (int) $state->id,
                (int) $state->accommodation_id,
                (string) ($state->provider?->code ?? $state->provider_id),
                ProviderRefreshStateStatus::name((int) $state->status),
                (string) ($state->outcome ?? '-'),
                (int) $state->attempts,
                $state->next_attempt_at?->format('Y-m-d H:i:s') ?? '-',
                $state->completed_at?->format('Y-m-d H:i:s') ?? '-',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
