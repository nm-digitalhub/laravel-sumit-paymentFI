# API Reference

## Table of Contents

1. [Facades](#facades)
2. [Services](#services)
3. [Models](#models)
4. [Events](#events)
5. [Routes](#routes)
6. [Configuration](#configuration)

## Facades

### SumitPayment

The main facade for accessing payment services.

#### Methods

##### processPayment(array $paymentData): array

Process a payment transaction.

**Parameters:**

```php
$paymentData = [
    // Required
    'amount' => 100.00,                    // float
    'customer_name' => 'John Doe',         // string
    'customer_email' => 'john@example.com', // string
    
    // Required if not using token
    'card_number' => '4580000000000000',   // string
    'expiry_month' => '12',                // string (2 digits)
    'expiry_year' => '25',                 // string (2 or 4 digits)
    
    // Optional
    'cvv' => '123',                        // string
    'currency' => 'ILS',                   // string
    'order_id' => 'ORD-12345',            // string
    'user_id' => 1,                        // int
    'description' => 'Order payment',      // string
    'payments_count' => 1,                 // int (1-12)
    'is_subscription' => false,            // bool
    'is_donation' => false,                // bool
    'customer_phone' => '+972501234567',   // string
    'customer_address' => 'Street 123',    // string
    'customer_city' => 'Tel Aviv',         // string
    'customer_country' => 'IL',            // string
    'customer_zip' => '12345',             // string
    'items' => [                           // array
        [
            'Name' => 'Product 1',
            'Price' => 50.00,
            'Quantity' => 2,
        ],
    ],
    'metadata' => [                        // array
        'custom_field' => 'value',
    ],
    'vat_rate' => 17,                     // int
    'language' => 'he',                    // string (he/en)
];
```

**Returns:**

```php
[
    'success' => true,                     // bool
    'message' => 'Payment processed successfully', // string
    'transaction' => Transaction,          // Transaction model
    'response' => [                        // array (API response)
        'Status' => 'Success',
        'PaymentID' => '123',
        'DocumentID' => '456',
        // ... additional fields
    ],
]
```

##### processPaymentWithToken(array $paymentData, int $tokenId): array

Process payment using a saved token.

**Parameters:**

```php
$paymentData = [
    'amount' => 100.00,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'user_id' => 1,  // Optional, for validation
    // ... other optional fields
];

$tokenId = 1; // The ID of the saved token
```

**Returns:** Same as `processPayment()`

##### tokenizeCard(array $cardData, ?int $userId = null): array

Tokenize a credit card for future use.

**Parameters:**

```php
$cardData = [
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '25',
    'cvv' => '123',                      // Optional
    'cardholder_name' => 'John Doe',     // Optional
    'card_type' => 'Visa',               // Optional
    'is_default' => false,               // Optional
];

$userId = 1; // Optional, saves to user if provided
```

**Returns:**

```php
[
    'success' => true,
    'token' => PaymentToken,  // If userId provided
    'token_id' => 1,          // If userId provided
]
```

##### processRedirectCallback(string $transactionId, array $callbackData): array

Process callback from redirect payment flow.

**Parameters:**

```php
$transactionId = '123';
$callbackData = $_GET; // Callback parameters from SUMIT
```

**Returns:**

```php
[
    'success' => true,
    'transaction' => Transaction,
]
```

## Services

### ApiService

Handles communication with SUMIT API.

```php
use Sumit\LaravelPayment\Services\ApiService;

$apiService = app(ApiService::class);
```

#### Methods

##### post(array $request, string $path, bool $sendClientIp = true): ?array

Make a POST request to SUMIT API.

##### checkCredentials(): ?string

Validate API credentials. Returns null if valid, error message if invalid.

##### checkPublicCredentials(): ?string

Validate public API credentials. Returns null if valid, error message if invalid.

### PaymentService

Core payment processing service.

```php
use Sumit\LaravelPayment\Services\PaymentService;

$paymentService = app(PaymentService::class);
```

Methods are the same as the Facade (see above).

### TokenService

Manages payment tokens.

```php
use Sumit\LaravelPayment\Services\TokenService;

$tokenService = app(TokenService::class);
```

#### Methods

##### createToken(...): PaymentToken

Create a new payment token.

##### getUserTokens(int $userId, bool $activeOnly = true): Collection

Get all tokens for a user.

##### getDefaultToken(int $userId): ?PaymentToken

Get the default token for a user.

##### getToken(int $tokenId, ?int $userId = null): ?PaymentToken

Get a specific token.

##### setAsDefault(int $tokenId, int $userId): bool

Set a token as default.

##### deleteToken(int $tokenId, int $userId): bool

Delete a token.

##### cleanupExpiredTokens(): int

Remove expired tokens. Returns count of deleted tokens.

## Models

### Transaction

Represents a payment transaction.

```php
use Sumit\LaravelPayment\Models\Transaction;
```

#### Properties

- `id`: int
- `user_id`: ?int
- `order_id`: ?string
- `transaction_id`: ?string
- `payment_method`: string
- `amount`: decimal
- `currency`: string
- `status`: enum (pending, processing, completed, failed, refunded, cancelled)
- `payments_count`: int
- `description`: ?string
- `document_id`: ?string
- `document_type`: ?string
- `customer_id`: ?string
- `authorization_number`: ?string
- `last_four_digits`: ?string
- `is_subscription`: bool
- `is_donation`: bool
- `metadata`: ?array
- `error_message`: ?string
- `processed_at`: ?datetime
- `created_at`: datetime
- `updated_at`: datetime

#### Methods

##### isSuccessful(): bool

Check if transaction is successful.

##### isPending(): bool

Check if transaction is pending.

##### hasFailed(): bool

Check if transaction has failed.

##### markAsCompleted(string $transactionId = null, string $documentId = null): self

Mark transaction as completed.

##### markAsFailed(string $errorMessage = null): self

Mark transaction as failed.

#### Scopes

##### completed()

Get only completed transactions.

```php
$completed = Transaction::completed()->get();
```

##### pending()

Get only pending transactions.

```php
$pending = Transaction::pending()->get();
```

##### failed()

Get only failed transactions.

```php
$failed = Transaction::failed()->get();
```

##### subscriptions()

Get only subscription transactions.

```php
$subscriptions = Transaction::subscriptions()->get();
```

### PaymentToken

Represents a saved payment token.

```php
use Sumit\LaravelPayment\Models\PaymentToken;
```

#### Properties

- `id`: int
- `user_id`: ?int
- `token`: string (hidden from serialization)
- `card_type`: ?string
- `last_four`: string
- `expiry_month`: string
- `expiry_year`: string
- `cardholder_name`: ?string
- `is_default`: bool
- `expires_at`: ?datetime
- `created_at`: datetime
- `updated_at`: datetime

#### Methods

##### isExpired(): bool

Check if token is expired.

##### getMaskedCardNumber(): string

Get masked card number (e.g., "****-****-****-1234").

#### Scopes

##### default()

Get only default tokens.

```php
$default = PaymentToken::where('user_id', $userId)->default()->first();
```

##### active()

Get only active (non-expired) tokens.

```php
$active = PaymentToken::where('user_id', $userId)->active()->get();
```

### Customer

Represents a SUMIT customer.

```php
use Sumit\LaravelPayment\Models\Customer;
```

#### Properties

- `id`: int
- `user_id`: ?int
- `sumit_customer_id`: string
- `email`: ?string
- `phone`: ?string
- `name`: ?string
- `company_name`: ?string
- `tax_id`: ?string
- `address`: ?string
- `city`: ?string
- `state`: ?string
- `country`: string
- `zip_code`: ?string
- `metadata`: ?array
- `created_at`: datetime
- `updated_at`: datetime

#### Methods

##### getFullAddressAttribute(): string

Get formatted full address.

##### findBySumitId(string $sumitCustomerId): ?self

Find customer by SUMIT customer ID.

##### findOrCreateByUser(int $userId, array $data = []): self

Find or create customer for a user.

## Events

### PaymentCompleted

Dispatched when a payment is successfully completed.

```php
use Sumit\LaravelPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    // Handle successful payment
});
```

#### Properties

- `transaction`: Transaction

### PaymentFailed

Dispatched when a payment fails.

```php
use Sumit\LaravelPayment\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;
    $errorMessage = $event->errorMessage;
    // Handle failed payment
});
```

#### Properties

- `transaction`: Transaction
- `errorMessage`: string

### TokenCreated

Dispatched when a new payment token is created.

```php
use Sumit\LaravelPayment\Events\TokenCreated;

Event::listen(TokenCreated::class, function (TokenCreated $event) {
    $token = $event->token;
    // Handle new token
});
```

#### Properties

- `token`: PaymentToken

## Routes

All routes are prefixed with `/sumit` by default (configurable).

### Payment Routes

#### POST /sumit/payment/process

Process a payment.

**Request:**

```json
{
    "amount": 100.00,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "card_number": "4580000000000000",
    "expiry_month": "12",
    "expiry_year": "25",
    "cvv": "123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Payment processed successfully",
    "transaction": {
        "id": 1,
        "amount": "100.00",
        "status": "completed"
    }
}
```

#### GET /sumit/payment/callback

Handle redirect callback.

**Query Parameters:**
- `transaction`: Transaction ID
- (Additional SUMIT callback parameters)

#### GET /sumit/payment/{transactionId}

Get transaction details.

**Response:**

```json
{
    "success": true,
    "transaction": {
        "id": 1,
        "amount": "100.00",
        "status": "completed"
    }
}
```

### Token Routes (Authenticated)

#### GET /sumit/tokens

Get user's saved tokens.

**Response:**

```json
{
    "success": true,
    "tokens": [
        {
            "id": 1,
            "last_four": "1234",
            "expiry_month": "12",
            "expiry_year": "25",
            "is_default": true
        }
    ]
}
```

#### POST /sumit/tokens

Create a new token.

**Request:**

```json
{
    "card_number": "4580000000000000",
    "expiry_month": "12",
    "expiry_year": "25",
    "cvv": "123",
    "is_default": true
}
```

#### PUT /sumit/tokens/{tokenId}/default

Set token as default.

#### DELETE /sumit/tokens/{tokenId}

Delete a token.

## Configuration

All configuration is in `config/sumit-payment.php`.

### Key Configuration Options

```php
// API Credentials
'company_id' => env('SUMIT_COMPANY_ID'),
'api_key' => env('SUMIT_API_KEY'),
'api_public_key' => env('SUMIT_API_PUBLIC_KEY'),

// Environment
'environment' => env('SUMIT_ENVIRONMENT', 'www'),
'testing_mode' => env('SUMIT_TESTING_MODE', false),

// Merchant Settings
'merchant_number' => env('SUMIT_MERCHANT_NUMBER'),
'subscriptions_merchant_number' => env('SUMIT_SUBSCRIPTIONS_MERCHANT_NUMBER'),

// Document Settings
'draft_document' => env('SUMIT_DRAFT_DOCUMENT', false),
'email_document' => env('SUMIT_EMAIL_DOCUMENT', true),
'document_language' => env('SUMIT_DOCUMENT_LANGUAGE', 'he'),

// Payment Settings
'maximum_payments' => env('SUMIT_MAXIMUM_PAYMENTS', 12),
'token_method' => env('SUMIT_TOKEN_METHOD', 'J2'),
'pci_mode' => env('SUMIT_PCI_MODE', 'direct'),

// Database Tables
'tables' => [
    'payment_tokens' => 'sumit_payment_tokens',
    'transactions' => 'sumit_transactions',
    'customers' => 'sumit_customers',
],

// Routes
'routes' => [
    'prefix' => 'sumit',
    'middleware' => ['web'],
    'callback_url' => env('SUMIT_CALLBACK_URL', '/sumit/callback'),
],
```

### Customizing Table Names

```php
// In config/sumit-payment.php
'tables' => [
    'payment_tokens' => 'my_custom_tokens_table',
    'transactions' => 'my_custom_transactions_table',
    'customers' => 'my_custom_customers_table',
],
```

### Customizing Routes

```php
// In config/sumit-payment.php
'routes' => [
    'prefix' => 'payments',  // Changes /sumit/* to /payments/*
    'middleware' => ['web', 'custom'],  // Add custom middleware
    'callback_url' => '/custom/callback',
],
```
