<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\RoomCalendar;
use Illuminate\Support\Collection;

class RoomCalendarRepository
{
    public function bulkUpsert(
        int $providerId,
        int $accId,
        int $roomTypeId,
        int $ratePlanId,
        Collection $rows
    ): void {
        $now = now();
        $payload = $rows->map(fn($r) => [
            'accommodation_id' => $accId,
            'room_type_id' => $roomTypeId,
            'rate_plan_id' => $ratePlanId,
            'day' => $r['day'],
            'rack_rate' => $r['rack_rate'] ?? null,
            'daily_rate' => $r['daily_rate'] ?? null,
            'grs_rate' => $r['grs_rate'] ?? null,
            'min_stay' => $r['min_stay'] ?? null,
            'max_stay' => $r['max_stay'] ?? null,
            'cta' => (bool)($r['cta'] ?? false),
            'ctd' => (bool)($r['ctd'] ?? false),
            'closed' => (bool)($r['closed'] ?? false),
            'inventory' => $r['inventory'] ?? null,
            'provider_id' => $providerId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        RoomCalendar::query()->upsert(
            $payload,
            ['room_type_id', 'rate_plan_id', 'day', 'provider_id'],
            [
                'rack_rate','daily_rate','grs_rate',
                'min_stay','max_stay','cta','ctd','closed','inventory','updated_at'
            ]
        );
    }
}
