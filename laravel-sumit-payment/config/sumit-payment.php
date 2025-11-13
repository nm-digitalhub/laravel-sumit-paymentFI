<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SUMIT API Credentials
    |--------------------------------------------------------------------------
    |
    | Your SUMIT API credentials for payment processing
    |
    */
    'company_id' => env('SUMIT_COMPANY_ID', ''),
    'api_key' => env('SUMIT_API_KEY', ''),
    'api_public_key' => env('SUMIT_API_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    |
    | Set the environment for API calls. Options: 'www', 'dev'
    |
    */
    'environment' => env('SUMIT_ENVIRONMENT', 'www'),

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    |
    | Enable testing mode for development (authorize only, no capture)
    |
    */
    'testing_mode' => env('SUMIT_TESTING_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Merchant Settings
    |--------------------------------------------------------------------------
    |
    | Merchant numbers for different payment types
    |
    */
    'merchant_number' => env('SUMIT_MERCHANT_NUMBER', ''),
    'subscriptions_merchant_number' => env('SUMIT_SUBSCRIPTIONS_MERCHANT_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    |
    | Configure how invoices and receipts are generated
    |
    */
    'draft_document' => env('SUMIT_DRAFT_DOCUMENT', false),
    'email_document' => env('SUMIT_EMAIL_DOCUMENT', true),
    'document_language' => env('SUMIT_DOCUMENT_LANGUAGE', 'he'),

    /*
    |--------------------------------------------------------------------------
    | Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Configure authorization-only transactions (J5)
    |
    */
    'authorize_only' => env('SUMIT_AUTHORIZE_ONLY', false),
    'auto_capture' => env('SUMIT_AUTO_CAPTURE', true),
    'authorize_added_percent' => env('SUMIT_AUTHORIZE_ADDED_PERCENT', 0),
    'authorize_minimum_addition' => env('SUMIT_AUTHORIZE_MINIMUM_ADDITION', 0),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configure payment processing options
    |
    */
    'maximum_payments' => env('SUMIT_MAXIMUM_PAYMENTS', 12),
    'token_method' => env('SUMIT_TOKEN_METHOD', 'J2'), // J2 or J5

    /*
    |--------------------------------------------------------------------------
    | PCI Compliance
    |--------------------------------------------------------------------------
    |
    | Payment flow type: 'direct' or 'redirect'
    |
    */
    'pci_mode' => env('SUMIT_PCI_MODE', 'direct'),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure API connection settings
    |
    */
    'api_timeout' => env('SUMIT_API_TIMEOUT', 180),
    'send_client_ip' => env('SUMIT_SEND_CLIENT_IP', true),

    /*
    |--------------------------------------------------------------------------
    | VAT Settings
    |--------------------------------------------------------------------------
    |
    | Configure VAT calculation
    |
    */
    'vat_included' => env('SUMIT_VAT_INCLUDED', true),
    'default_vat_rate' => env('SUMIT_DEFAULT_VAT_RATE', 17),

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
