<?php

namespace Tests\Feature;

use App\Models\RoomCalendar;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoomCalendarPruningTest extends TestCase
{
    public function test_pruning_excludes_today_and_future_dates_in_tehran(): void
    {
        // 21:00 UTC is 00:30 the following day in Tehran.
        Carbon::setTestNow(Carbon::parse('2026-09-19 21:00:00', 'UTC'));

        try {
            $query = (new RoomCalendar)->prunable();

            $this->assertSame(['2026-09-20'], $query->getBindings());
            $this->assertStringContainsString('day', $query->toSql());
            $this->assertStringContainsString('<', $query->toSql());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_pruning_uses_previous_tehran_day_before_midnight(): void
    {
        // 20:00 UTC is 23:30 on the same date in Tehran.
        Carbon::setTestNow(Carbon::parse('2026-09-19 20:00:00', 'UTC'));

        try {
            $this->assertSame(
                ['2026-09-19'],
                (new RoomCalendar)->prunable()->getBindings()
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
