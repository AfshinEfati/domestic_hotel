<?php

return [
    // SSP is the source of truth for which GRS hotels need a price refresh.
    // These credentials must be configured in the local .env, never committed.
    'shared_db' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST_SHARE', '127.0.0.1'),
        'port' => env('DB_PORT_SHARE', '3306'),
        'database' => env('DB_DATABASE_SHARE', ''),
        'username' => env('DB_USERNAME_SHARE', ''),
        'password' => env('DB_PASSWORD_SHARE', ''),
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
    ],
    'availability' => [
        'default_days' => 90,
        // Set to 1 for the first live test, then increase after inspecting results.
        'dispatch_limit' => (int) env('GRS_PRICES_DISPATCH_LIMIT', 10),
        'claim_minutes' => 15,
        'failure_backoff_minutes' => 15,
        // Disabled by default: opt in AFTER the shared migration and tests succeed.
        'scheduler_enabled' => (bool) env('GRS_PRICES_SCHEDULER_ENABLED', false),
    ],
];
