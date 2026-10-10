<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use Illuminate\Http\Client\Response;

final class SnappTripCalendarRangeLimit
{
    public static function fromResponse(?Response $response): ?int
    {
        if ($response === null || $response->status() !== 400) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        $code = strtoupper(trim((string) ($payload['code'] ?? '')));
        $message = trim((string) ($payload['message'] ?? ''));
        if ($code !== 'INVALID_DATE' || $message === '') {
            return null;
        }

        $patterns = [
            '/date\s+range.*?(?:cannot|can\s+not|must\s+not|should\s+not).*?(?:more\s+than|over)\s+(\d+)\s+days?/i',
            '/date\s+range.*?(?:cannot|can\s+not|must\s+not|should\s+not)?\s*exceed\s+(\d+)\s+days?/i',
            '/date\s+range.*?(?:maximum|max(?:imum)?\s+of)\s+(\d+)\s+days?/i',
            '/date\s+range.*?(?:at\s+most|up\s+to)\s+(\d+)\s+days?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches) !== 1) {
                continue;
            }

            $days = (int) ($matches[1] ?? 0);
            return $days > 0 ? $days : null;
        }

        return null;
    }
}
