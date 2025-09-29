<?php

return [
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
