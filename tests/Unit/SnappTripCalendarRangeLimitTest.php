<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarRangeLimit;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class SnappTripCalendarRangeLimitTest extends TestCase
{
    public function test_extracts_provider_reported_maximum_days(): void
    {
        $response = new Response(new Psr7Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode([
                'success' => false,
                'message' => 'date range cannot be more than 20 days',
                'error' => '',
                'code' => 'INVALID_DATE',
            ], JSON_THROW_ON_ERROR),
        ));

        $this->assertSame(20, SnappTripCalendarRangeLimit::fromResponse($response));
    }

    public function test_ignores_unrelated_invalid_date_errors(): void
    {
        $response = new Response(new Psr7Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode([
                'success' => false,
                'message' => 'check-in date is invalid',
                'code' => 'INVALID_DATE',
            ], JSON_THROW_ON_ERROR),
        ));

        $this->assertNull(SnappTripCalendarRangeLimit::fromResponse($response));
    }
}
