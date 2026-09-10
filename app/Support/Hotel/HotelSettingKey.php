<?php

namespace App\Support\Hotel;

final class HotelSettingKey
{
    public const PRICING_DEFAULT_PERCENTAGE = 'pricing.default_percentage';
    public const PRICING_DEFAULT_FIXED_AMOUNT = 'pricing.default_fixed_amount';
    public const RESERVATION_REFERENCE_PREFIX = 'reservation.reference_prefix';
    public const RESERVATION_REFERENCE_RANDOM_LENGTH = 'reservation.reference_random_length';
    public const RESERVATION_REFERENCE_MAX_ATTEMPTS = 'reservation.reference_max_attempts';

    private function __construct()
    {
    }
}
