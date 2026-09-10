<?php

return [
    'pricing' => [
        'default_percentage' => (float) env('HOTEL_DEFAULT_MARKUP_PERCENTAGE', 5),
        'default_fixed_amount' => (int) env('HOTEL_DEFAULT_MARKUP_FIXED_AMOUNT', 0),
    ],

    'reservation' => [
        'reference_prefix' => env('HOTEL_RESERVATION_REFERENCE_PREFIX', 'DH'),
        'reference_random_length' => (int) env('HOTEL_RESERVATION_REFERENCE_RANDOM_LENGTH', 10),
    ],

    'providers' => [
        'grs' => [
            'availability' => [
                'days' => env('GRS_AVAILABILITY_DAYS', 60),
                'chunk_size' => env('GRS_AVAILABILITY_CHUNK', 20),
                'throttle_ms' => env('GRS_AVAILABILITY_THROTTLE_MS', 500),
                'max_attempts' => env('GRS_AVAILABILITY_MAX_ATTEMPTS', 1),
                'requests_per_minute' => env('GRS_AVAILABILITY_REQUESTS_PER_MINUTE', 10),
            ],
        ],
    ],
];
