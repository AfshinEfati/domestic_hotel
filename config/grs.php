<?php

return [
    // Connection credentials only. All GRS refresh operations are configured
    // by admins in providers.config.price_refresh, NOT by .env or this file.
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
];
