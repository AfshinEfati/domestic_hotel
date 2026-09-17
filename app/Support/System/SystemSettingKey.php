<?php

namespace App\Support\System;

final class SystemSettingKey
{
    public const PRICING_DEFAULT_PERCENTAGE = 'pricing.default_percentage';
    public const PRICING_DEFAULT_FIXED_AMOUNT = 'pricing.default_fixed_amount';
    public const RESERVATION_REFERENCE_PREFIX = 'reservation.reference_prefix';
    public const RESERVATION_REFERENCE_RANDOM_LENGTH = 'reservation.reference_random_length';
    public const RESERVATION_REFERENCE_MAX_ATTEMPTS = 'reservation.reference_max_attempts';
    public const GRS_AVAILABILITY_DAYS = 'provider.grs.availability.days';
    public const GRS_AVAILABILITY_CHUNK_SIZE = 'provider.grs.availability.chunk_size';
    public const GRS_AVAILABILITY_THROTTLE_MS = 'provider.grs.availability.throttle_ms';
    public const GRS_AVAILABILITY_MAX_ATTEMPTS = 'provider.grs.availability.max_attempts';

    private function __construct()
    {
    }
}
