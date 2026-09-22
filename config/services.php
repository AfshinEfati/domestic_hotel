<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for such credentials.
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Secrets stay in the server's untracked .env, never in Git history.
    'telegram_alert' => [
        'url' => 'https://ehotelo.com/api/telegram/send-message',
        'token' => env('TELEGRAM_ALERT_TOKEN'),
        'chat_id' => env('TELEGRAM_ALERT_CHAT_ID'),
        // Set to parse_mode only if the company proxy forwards it to Telegram.
        'parse_mode_field' => env('TELEGRAM_ALERT_PARSE_MODE_FIELD', 'parse_mode'),
    ],

];
