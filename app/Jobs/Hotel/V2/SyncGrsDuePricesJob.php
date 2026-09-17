<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Select due GRS properties ONLY from the shared SSP scheduler. No provider HTTP. */
class SyncGrsDuePricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;
    public int $uniqueFor = 600;

    public function __construct(public ?int $days = null)
    {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        // Do not overlap two scans with different day windows.
        return 'grs-due-prices-dispatch';
    }

    public function handle(): void
    {
        $days = $this->days ?? (int) config('grs.availability.default_days', 90);
        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('GRS availability days must be between 1 and 3650.');
        }

        $provider = Provider::query()->where('code', 'grs')->firstOrFail();
        if (!$provider->is_active || !$provider->is_online) {
            Log::warning('GRS prices skipped: provider inactive or offline');
            return;
        }

        if (RateLimitedGrsAdapter::cooldownSeconds() > 0) {
            Log::warning('GRS prices paused by API 429 cooldown');
            return;
        }

        if (trim((string) config('grs.shared_db.database')) === '' ||
            trim((string) config('grs.shared_db.username')) === '') {
            throw new RuntimeException('Set DB_HOST_SHARE, DB_DATABASE_SHARE, DB_USERNAME_SHARE and DB_PASSWORD_SHARE before GRS pricing.');
        }

        foreach (['grs_id', 'next_gds_run_at'] as $column) {
            if (!Schema::connection('shared_ssp')->hasColumn('hotel_price_refresh_schedules', $column)) {
                throw new RuntimeException("Shared SSP hotel_price_refresh_schedules.{$column} must exist before GRS pricing.");
            }
        }

        $db = DB::connection('shared_ssp');
        $now = now();
        $limit = max(1, min(100, (int) config('grs.availability.dispatch_limit', 10)));
        $claimMinutes = max(5, (int) config('grs.availability.claim_minutes', 15));

        $due = $db->table('hotel_price_refresh_schedules')
            ->where('is_active', true)
            ->whereNotNull('grs_id')
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_gds_run_at')->orWhere('next_gds_run_at', '<=', $now);
            })
            ->orderBy('next_gds_run_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get(['id', 'grs_id', 'next_gds_run_at', 'refresh_interval_minutes']);

        $queued = 0;
        $missing = 0;
        foreach ($due as $schedule) {
            $grsId = trim((string) $schedule->grs_id);
            if ($grsId === '') {
                continue;
            }

            // Compare-and-swap claim so multiple schedulers cannot dispatch the same row.
            // The claim expires: a lost job can be selected again after claimMinutes.
            $claim = $db->table('hotel_price_refresh_schedules')
                ->where('id', $schedule->id)
                ->where('is_active', true)
                ->where('grs_id', $schedule->grs_id);
            if ($schedule->next_gds_run_at === null) {
                $claim->whereNull('next_gds_run_at');
            } else {
                $claim->where('next_gds_run_at', $schedule->next_gds_run_at);
            }
            if ($claim->update(['next_gds_run_at' => now()->addMinutes($claimMinutes)]) !== 1) {
                continue;
            }

            if (!AccommodationProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('provider_property_id', $grsId)
                ->exists()) {
                $missing++;
                $db->table('hotel_price_refresh_schedules')->where('id', $schedule->id)
                    ->where('grs_id', $schedule->grs_id)
                    ->update(['next_gds_run_at' => now()->addHour()]);
                Log::warning('GRS due property missing local map; no API call', [
                    'schedule_id' => $schedule->id, 'grs_id' => $grsId,
                ]);
                continue;
            }

            RefreshGrsPropertyPricesJob::dispatch(
                (int) $schedule->id,
                $grsId,
                (int) $provider->id,
                $days,
                max(1, (int) $schedule->refresh_interval_minutes)
            );
            $queued++;
        }

        Log::info('GRS due price scan finished', [
            'days' => $days, 'due' => $due->count(), 'queued' => $queued, 'unmapped' => $missing,
        ]);
    }
}
