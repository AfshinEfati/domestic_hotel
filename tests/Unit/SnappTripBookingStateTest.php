<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripBookingState;
use PHPUnit\Framework\TestCase;

class SnappTripBookingStateTest extends TestCase
{
    public function test_documented_booking_states_are_classified_safely(): void
    {
        $this->assertTrue(SnappTripBookingState::isConfirmed('confirmed'));
        $this->assertTrue(SnappTripBookingState::needsConfirm('draft'));
        $this->assertTrue(SnappTripBookingState::needsConfirm('payment_on'));

        foreach ([
            'draft',
            'payment_on',
            'availability_checking',
            'availability_pending',
            'availability_confirmed',
        ] as $state) {
            $this->assertTrue(SnappTripBookingState::isPending($state));
        }

        foreach ([
            'failed',
            'payment_failed',
            'expired',
            'availability_failed',
            'availability_rejected',
            'overbook',
        ] as $state) {
            $this->assertTrue(SnappTripBookingState::isTerminalFailure($state));
        }

        $this->assertFalse(SnappTripBookingState::isConfirmed('unexpected'));
        $this->assertFalse(SnappTripBookingState::isPending('unexpected'));
        $this->assertFalse(SnappTripBookingState::isTerminalFailure('unexpected'));
    }

    public function test_state_normalization_is_case_and_whitespace_safe(): void
    {
        $this->assertSame('confirmed', SnappTripBookingState::normalize('  CONFIRMED '));
        $this->assertNull(SnappTripBookingState::normalize(''));
        $this->assertNull(SnappTripBookingState::normalize(null));
    }
}
