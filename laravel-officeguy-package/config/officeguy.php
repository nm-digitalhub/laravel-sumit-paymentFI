<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Credentials
    |--------------------------------------------------------------------------
    |
    | Your SUMIT company credentials for API access.
    | You can find these in your SUMIT dashboard at:
    | https://app.sumit.co.il/developers/keys/
    |
    */
    'company_id' => env('OFFICEGUY_COMPANY_ID', ''),
    'api_private_key' => env('OFFICEGUY_PRIVATE_KEY', ''),
    'api_public_key' => env('OFFICEGUY_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The API environment to use. Options: 'www' (production), 'dev' (development)
    |
    */
    'environment' => env('OFFICEGUY_ENVIRONMENT', 'www'),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url' => env('OFFICEGUY_API_URL', 'https://api.sumit.co.il'),
        'timeout' => env('OFFICEGUY_API_TIMEOUT', 180),
        'verify_ssl' => env('OFFICEGUY_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'merchant_number' => env('OFFICEGUY_MERCHANT_NUMBER', ''),
        'subscriptions_merchant_number' => env('OFFICEGUY_SUBSCRIPTIONS_MERCHANT_NUMBER', ''),
        'testing_mode' => env('OFFICEGUY_TESTING_MODE', false),
        'authorize_only' => env('OFFICEGUY_AUTHORIZE_ONLY', false),
        'auto_capture' => env('OFFICEGUY_AUTO_CAPTURE', true),
        'draft_document' => env('OFFICEGUY_DRAFT_DOCUMENT', false),
        'send_document_by_email' => env('OFFICEGUY_SEND_DOCUMENT_BY_EMAIL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    */
    'payment_limits' => [
        'max_payments' => env('OFFICEGUY_MAX_PAYMENTS', 1),
        'min_amount_for_payments' => env('OFFICEGUY_MIN_AMOUNT_FOR_PAYMENTS', 0),
        'min_amount_per_payment' => env('OFFICEGUY_MIN_AMOUNT_PER_PAYMENT', 0),
        'authorize_added_percent' => env('OFFICEGUY_AUTHORIZE_ADDED_PERCENT', 0),
        'authorize_minimum_addition' => env('OFFICEGUY_AUTHORIZE_MINIMUM_ADDITION', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Settings
    |--------------------------------------------------------------------------
    */
    'customer' => [
        'merge_customers' => env('OFFICEGUY_MERGE_CUSTOMERS', false),
        'update_on_success' => env('OFFICEGUY_UPDATE_ON_SUCCESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    */
    'document' => [
        'language' => env('OFFICEGUY_DOCUMENT_LANGUAGE', 'he'),
        'automatic_languages' => env('OFFICEGUY_AUTOMATIC_LANGUAGES', true),
        'vat_included' => env('OFFICEGUY_VAT_INCLUDED', true),
        'default_vat_rate' => env('OFFICEGUY_DEFAULT_VAT_RATE', 17),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    */
    'tokens' => [
        'support_tokens' => env('OFFICEGUY_SUPPORT_TOKENS', true),
        'token_param' => env('OFFICEGUY_TOKEN_PARAM', '5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Synchronization
    |--------------------------------------------------------------------------
    */
    'stock' => [
        'sync_on_checkout' => env('OFFICEGUY_STOCK_SYNC_ON_CHECKOUT', false),
        'sync_frequency' => env('OFFICEGUY_STOCK_SYNC_FREQUENCY', 'none'), // 'none', '12', '24'
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('OFFICEGUY_LOGGING_ENABLED', true),
        'channel' => env('OFFICEGUY_LOG_CHANNEL', 'stack'),
        'level' => env('OFFICEGUY_LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'supported_currencies' => ['ILS', 'USD', 'EUR', 'GBP'],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('OFFICEGUY_ROUTE_PREFIX', 'officeguy'),
        'middleware' => ['api'],
        'webhook_middleware' => ['api', 'officeguy.verify'],
        'success_url' => env('OFFICEGUY_SUCCESS_URL', '/payment/success'),
        'failure_url' => env('OFFICEGUY_FAILURE_URL', '/payment/failure'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'verify_signature' => env('OFFICEGUY_VERIFY_WEBHOOK_SIGNATURE', false),
    ],
];
