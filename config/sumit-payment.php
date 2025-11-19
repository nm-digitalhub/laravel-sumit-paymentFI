<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Customize database table names
    |
    */
    'tables' => [
        'payment_tokens' => 'sumit_payment_tokens',
        'transactions' => 'sumit_transactions',
        'customers' => 'sumit_customers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for payment operations
    |
    */
    'logging' => [
        'enabled' => env('SUMIT_LOGGING_ENABLED', true),
        'channel' => env('SUMIT_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configure route settings for callbacks and webhooks
    |
    */
    'routes' => [
        'prefix' => 'sumit',
        'middleware' => ['web'],
        'callback_url' => env('SUMIT_CALLBACK_URL', '/sumit/callback'),
    ],
];
