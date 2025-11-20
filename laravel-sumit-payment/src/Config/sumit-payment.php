<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SUMIT API Credentials
    |--------------------------------------------------------------------------
    |
    | Your SUMIT API credentials. You can find these in your SUMIT account.
    |
    */
    'company_id' => env('SUMIT_COMPANY_ID', ''),
    'api_key' => env('SUMIT_API_KEY', ''),
    'api_public_key' => env('SUMIT_API_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The SUMIT environment to use. Options: 'www' (production), 'dev' (development)
    |
    */
    'environment' => env('SUMIT_ENVIRONMENT', 'www'),

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, transactions will be marked as test transactions.
    |
    */
    'testing_mode' => env('SUMIT_TESTING_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Merchant Settings
    |--------------------------------------------------------------------------
    |
    | Merchant numbers for different transaction types.
    |
    */
    'merchant_number' => env('SUMIT_MERCHANT_NUMBER', ''),
    'subscription_merchant_number' => env('SUMIT_SUBSCRIPTION_MERCHANT_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configure payment processing options.
    |
    */
    'pci_compliance' => env('SUMIT_PCI_COMPLIANCE', 'no'), // 'yes', 'no', 'redirect'
    'token_param' => env('SUMIT_TOKEN_PARAM', 'J5'), // 'J2' or 'J5'
    'support_tokens' => env('SUMIT_SUPPORT_TOKENS', true),
    'authorize_only' => env('SUMIT_AUTHORIZE_ONLY', false),
    'auto_capture' => env('SUMIT_AUTO_CAPTURE', true),

    /*
    |--------------------------------------------------------------------------
    | Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Settings for authorization-only transactions.
    |
    */
    'authorize_added_percent' => env('SUMIT_AUTHORIZE_ADDED_PERCENT', 0),
    'authorize_minimum_addition' => env('SUMIT_AUTHORIZE_MINIMUM_ADDITION', 0),

    /*
    |--------------------------------------------------------------------------
    | Installments Settings
    |--------------------------------------------------------------------------
    |
    | Configure installment payment options.
    |
    */
    'max_payments' => env('SUMIT_MAX_PAYMENTS', 12),
    'min_amount_per_payment' => env('SUMIT_MIN_AMOUNT_PER_PAYMENT', 0),
    'min_amount_for_payments' => env('SUMIT_MIN_AMOUNT_FOR_PAYMENTS', 0),

    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    |
    | Configure invoice/receipt generation options.
    |
    */
    'draft_document' => env('SUMIT_DRAFT_DOCUMENT', false),
    'email_document' => env('SUMIT_EMAIL_DOCUMENT', true),
    'create_order_document' => env('SUMIT_CREATE_ORDER_DOCUMENT', false),
    'automatic_languages' => env('SUMIT_AUTOMATIC_LANGUAGES', true),

    /*
    |--------------------------------------------------------------------------
    | Customer Settings
    |--------------------------------------------------------------------------
    |
    | Configure customer management options.
    |
    */
    'merge_customers' => env('SUMIT_MERGE_CUSTOMERS', true),

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configure field validation requirements.
    |
    */
    'cvv_required' => env('SUMIT_CVV_REQUIRED', false),
    'citizen_id_required' => env('SUMIT_CITIZEN_ID_REQUIRED', false),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for debugging purposes.
    |
    */
    'logging' => env('SUMIT_LOGGING', false),
    'log_channel' => env('SUMIT_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported by SUMIT.
    |
    */
    'supported_currencies' => [
        'ILS', 'USD', 'EUR', 'CAD', 'GBP', 'CHF', 'AUD', 'JPY', 
        'SEK', 'NOK', 'DKK', 'ZAR', 'JOD', 'LBP', 'EGP', 'BGN', 
        'CZK', 'HUF', 'PLN', 'RON', 'ISK', 'HRK', 'RUB', 'TRY', 
        'BRL', 'CNY', 'HKD', 'IDR', 'INR', 'KRW', 'MXN', 'MYR', 
        'NZD', 'PHP', 'SGD', 'THB'
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configure webhook endpoints and security.
    |
    */
    'webhook_secret' => env('SUMIT_WEBHOOK_SECRET', ''),
    'webhook_enabled' => env('SUMIT_WEBHOOK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    |
    | Configure routing options for the package.
    |
    */
    'routes' => [
        'prefix' => env('SUMIT_ROUTE_PREFIX', 'sumit'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Configure database table names.
    |
    */
    'tables' => [
        'payment_tokens' => 'sumit_payment_tokens',
        'transactions' => 'sumit_transactions',
        'customers' => 'sumit_customers',
        'documents' => 'sumit_documents',
    ],
];
