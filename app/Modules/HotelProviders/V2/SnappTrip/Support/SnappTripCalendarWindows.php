<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class SnappTripCalendarWindows
{
    /** SnappTrip rejects calendar ranges longer than 40 days with INVALID_DATE. */
    public const MAX_DAYS = 40;

    /**
     * @return array<int,array{from:string,to:string}>
     */
    public static function split(string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        if (!$start->lt($end)) {
            throw new InvalidArgumentException('SnappTrip calendar end date must be after start date.');
        }

        $windows = [];
        $cursor = $start;

        while ($cursor->lt($end)) {
            $windowEnd = $cursor->addDays(self::MAX_DAYS);
            if ($windowEnd->gt($end)) {
                $windowEnd = $end;
            }

            $windows[] = [
                'from' => $cursor->toDateString(),
                'to' => $windowEnd->toDateString(),
            ];

            $cursor = $windowEnd;
        }

        return $windows;
    }

    public static function chunkCountForDays(int $days): int
    {
        if ($days < 1) {
            throw new InvalidArgumentException('SnappTrip calendar days must be positive.');
        }

        return intdiv($days + self::MAX_DAYS - 1, self::MAX_DAYS);
    }

    public static function requestCountForDays(int $days): int
    {
        // Each window is fetched once for domestic guests and once for foreign guests.
        return self::chunkCountForDays($days) * 2;
    }
}
