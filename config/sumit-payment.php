<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SUMIT API Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to authenticate with the SUMIT API.
    | You can obtain them from your SUMIT merchant dashboard.
    |
    */
    'api' => [
        'company_id' => env('SUMIT_COMPANY_ID'),
        'api_key' => env('SUMIT_API_KEY'),
        'api_public_key' => env('SUMIT_API_PUBLIC_KEY'),
        'merchant_number' => env('SUMIT_MERCHANT_NUMBER'),
        'environment' => env('SUMIT_ENVIRONMENT', 'www'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration (Optional)
    |--------------------------------------------------------------------------
    |
    | If you want to use the package's models, configure them here.
    | If you DON'T want to use them, set to null - use events instead.
    | The package will fire events for all operations, allowing you to
    | handle data storage in your own way.
    |
    */
    'models' => [
        'transaction' => env('SUMIT_TRANSACTION_MODEL', \Sumit\LaravelPayment\Models\Transaction::class),
        'token' => env('SUMIT_TOKEN_MODEL', \Sumit\LaravelPayment\Models\PaymentToken::class),
        'customer' => env('SUMIT_CUSTOMER_MODEL', \Sumit\LaravelPayment\Models\Customer::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Customize database table names (only used if models are enabled)
    |
    */
    'tables' => [
        'payment_tokens' => env('SUMIT_TABLE_TOKENS', 'sumit_payment_tokens'),
        'transactions' => env('SUMIT_TABLE_TRANSACTIONS', 'sumit_transactions'),
        'customers' => env('SUMIT_TABLE_CUSTOMERS', 'sumit_customers'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Bindings (Customize implementations)
    |--------------------------------------------------------------------------
    |
    | You can override the default service implementations by binding
    | your own classes to these interfaces.
    |
    */
    'services' => [
        'payment_gateway' => \Sumit\LaravelPayment\Services\PaymentService::class,
        'token_storage' => null, // User provides their own implementation or uses default
        'webhook_handler' => \Sumit\LaravelPayment\Controllers\WebhookController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for receiving payment status updates.
    |
    */
    'webhooks' => [
        'enabled' => env('SUMIT_WEBHOOKS_ENABLED', true),
        'path' => env('SUMIT_WEBHOOK_PATH', 'sumit/webhook'),
        'signature_verification' => env('SUMIT_VERIFY_SIGNATURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callbacks (Optional - for quick customization)
    |--------------------------------------------------------------------------
    |
    | You can define callbacks that will be executed at specific points
    | during payment processing. These are invoked before events are fired.
    |
    */
    'callbacks' => [
        'before_payment' => null,          // function(PaymentData $data): void
        'after_payment_success' => null,   // function(PaymentResponse $response): void
        'after_payment_failure' => null,   // function(string $errorMessage, PaymentData $data): void
        'before_refund' => null,           // function(RefundData $data): void
        'after_refund' => null,            // function(PaymentResponse $response): void
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
        'prefix' => env('SUMIT_ROUTE_PREFIX', 'sumit'),
        'middleware' => ['web'],
        'callback_url' => env('SUMIT_CALLBACK_URL', '/sumit/callback'),
    ],
];

