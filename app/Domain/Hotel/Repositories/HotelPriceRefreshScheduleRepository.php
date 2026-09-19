<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\HotelPriceRefreshSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** All access to the SSP-owned refresh schedule stays on its named connection. */
class HotelPriceRefreshScheduleRepository
{
    public function assertReady(): void
    {
        $connection = config('database.connections.shared_ssp');
        if (!is_array($connection) || !isset($connection['driver']) ||
            (in_array($connection['driver'], ['mysql', 'mariadb'], true) &&
                (trim((string) ($connection['database'] ?? '')) === '' ||
                    trim((string) ($connection['username'] ?? '')) === ''))) {
            throw new RuntimeException('Configure the shared_ssp connection using the DB_*_SHARE credentials.');
        }

        foreach (['grs_id', 'next_gds_run_at', 'last_gds_success_run_at', 'last_gds_success_at'] as $column) {
            if (!Schema::connection('shared_ssp')->hasColumn('hotel_price_refresh_schedules', $column)) {
                throw new RuntimeException("SSP hotel_price_refresh_schedules.{$column} must exist before GRS pricing.");
            }
        }
    }

    /** @return Collection<int, HotelPriceRefreshSchedule> */
    public function due(int $requestCapacity): Collection
    {
        return HotelPriceRefreshSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('grs_id')
            ->where('grs_id', '<>', '')
            ->where(function ($query): void {
                $query->whereNull('next_gds_run_at')
                    ->orWhereRaw('next_gds_run_at <= CURRENT_TIMESTAMP');
            })
            ->orderBy('next_gds_run_at')
            ->orderBy('id')
            ->limit($requestCapacity)
            ->get();
    }

    public function active(int $id, string $grsId): ?HotelPriceRefreshSchedule
    {
        return HotelPriceRefreshSchedule::query()
            ->whereKey($id)
            ->where('grs_id', $grsId)
            ->where('is_active', true)
            ->first();
    }

    /** Called after API quota has been acquired, just before availability HTTP. */
    public function markRequestStarted(int $id, string $grsId): void
    {
        $this->updateTime($id, $grsId, 'last_gds_success_run_at');
    }

    /** HTTP 200 counts even if subsequent room/calendar persistence fails. */
    public function markHttp200(int $id, string $grsId): void
    {
        $this->updateTime($id, $grsId, 'last_gds_success_at');
    }

    /** The due time changes ONLY after verified local persistence. */
    public function markPersisted(int $id, string $grsId): int
    {
        $schedule = $this->active($id, $grsId);
        if ($schedule === null) {
            throw new RuntimeException('SSP price refresh schedule is no longer active or mapped.');
        }

        $minutes = max(1, (int) $schedule->refresh_interval_minutes);
        $next = $this->clock()->addMinutes($minutes)->toDateTimeString();
        $updated = HotelPriceRefreshSchedule::query()
            ->whereKey($id)
            ->where('grs_id', $grsId)
            ->where('is_active', true)
            ->update(['next_gds_run_at' => $next]);
        if ($updated !== 1) {
            throw new RuntimeException('Unable to update the SSP next GDS refresh time.');
        }

        return $minutes;
    }

    private function updateTime(int $id, string $grsId, string $column): void
    {
        $updated = HotelPriceRefreshSchedule::query()
            ->whereKey($id)
            ->where('grs_id', $grsId)
            ->where('is_active', true)
            ->update([$column => $this->clock()->toDateTimeString()]);
        if ($updated !== 1) {
            throw new RuntimeException("Unable to update SSP {$column}: schedule is inactive or missing.");
        }
    }

    private function clock(): CarbonImmutable
    {
        $connection = (new HotelPriceRefreshSchedule())->getConnection();
        return CarbonImmutable::parse($connection->selectOne('SELECT CURRENT_TIMESTAMP AS db_now')->db_now);
    }
}
