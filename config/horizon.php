<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),
    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
        'redis:grs-hotels' => 60,
        'redis:grs-prices' => 60,
        'redis:grs-details' => 60,
        'redis:snapptrip-static' => 60,
        'redis:snapptrip-prices' => 60,
        'redis:snapptrip-operations' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit' => 64,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'grs-hotels', 'grs-prices'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => env('HORIZON_JOB_TRIES', 2),
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-grs-details' => [
            'connection' => 'redis',
            'queue' => ['grs-details'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => env('HORIZON_JOB_TRIES', 2),
            'timeout' => 80,
            'nice' => 0,
        ],
        'supervisor-snapptrip' => [
            'connection' => 'redis',
            'queue' => ['snapptrip-static', 'snapptrip-prices', 'snapptrip-operations'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => env('HORIZON_JOB_TRIES', 2),
            'timeout' => 100,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-grs-details' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-snapptrip' => [
                'maxProcesses' => 6,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
            'supervisor-grs-details' => [
                'maxProcesses' => 3,
            ],
            'supervisor-snapptrip' => [
                'maxProcesses' => 2,
            ],
        ],
    ],
];
