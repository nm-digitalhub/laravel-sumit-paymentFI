# SUMIT Payment Gateway for Laravel

A comprehensive Laravel package for integrating SUMIT payment gateway with your Laravel 11+ application. This package provides secure payment processing, credit card tokenization, invoice generation, and recurring billing capabilities.

## Features

- 💳 **Secure Payment Processing** - Process credit card payments with PCI compliance
- 🔐 **Token Storage** - Securely store customer payment methods for future use
- 📄 **Invoice Generation** - Automatically generate and email invoices/receipts
- 🔄 **Recurring Billing** - Support for subscription and recurring payments
- 🎯 **Events System** - Laravel events for payment lifecycle hooks
- 🛡️ **Security** - Built-in security features and encryption
- 🌐 **Multi-Currency** - Support for multiple currencies
- 📱 **Redirect & Direct** - Both redirect and direct payment flows

## Requirements

- PHP 8.1 or higher
- Laravel 11.x or 12.x
- SUMIT merchant account

## Installation

Install the package via Composer:

```bash
composer require sumit/laravel-payment-gateway
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=sumit-payment-migrations
php artisan migrate
```

## Configuration

Add your SUMIT credentials to your `.env` file:

```env
SUMIT_COMPANY_ID=your-company-id
SUMIT_API_KEY=your-api-key
SUMIT_API_PUBLIC_KEY=your-public-key
SUMIT_MERCHANT_NUMBER=your-merchant-number
SUMIT_ENVIRONMENT=www

# Optional settings
SUMIT_TESTING_MODE=false
SUMIT_PCI_MODE=direct
SUMIT_EMAIL_DOCUMENT=true
SUMIT_DOCUMENT_LANGUAGE=he
SUMIT_MAXIMUM_PAYMENTS=12
```

## Usage

### Basic Payment Processing

```php
use Sumit\LaravelPayment\Facades\SumitPayment;

$result = SumitPayment::processPayment([
    'amount' => 100.00,
    'currency' => 'ILS',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '25',
    'cvv' => '123',
    'description' => 'Order #12345',
]);

if ($result['success']) {
    $transaction = $result['transaction'];
    // Payment successful
} else {
    // Payment failed
    $errorMessage = $result['message'];
}
```

### Tokenize a Credit Card

```php
$result = SumitPayment::tokenizeCard([
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '25',
    'cvv' => '123',
    'cardholder_name' => 'John Doe',
], auth()->id());

if ($result['success']) {
    $token = $result['token'];
    // Token saved
}
```

### Process Payment with Saved Token

```php
$result = SumitPayment::processPaymentWithToken([
    'amount' => 100.00,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
], $tokenId);
```

### Using the Service Directly

```php
use Sumit\LaravelPayment\Services\PaymentService;

class CheckoutController extends Controller
{
    public function processPayment(PaymentService $paymentService)
    {
        $result = $paymentService->processPayment([
            'amount' => 100.00,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            // ... other payment data
        ]);
        
        return response()->json($result);
    }
}
```

## Events

The package dispatches the following events:

### PaymentCompleted

Fired when a payment is successfully processed:

```php
use Sumit\LaravelPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    // Send confirmation email, update order status, etc.
});
```

### PaymentFailed

Fired when a payment fails:

```php
use Sumit\LaravelPayment\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;
    $errorMessage = $event->errorMessage;
    // Log error, notify admin, etc.
});
```

### TokenCreated

Fired when a new payment token is created:

```php
use Sumit\LaravelPayment\Events\TokenCreated;

Event::listen(TokenCreated::class, function (TokenCreated $event) {
    $token = $event->token;
    // Send notification to user
});
```

## Models

### Transaction

```php
use Sumit\LaravelPayment\Models\Transaction;

// Get all completed transactions
$completedTransactions = Transaction::completed()->get();

// Get pending transactions
$pendingTransactions = Transaction::pending()->get();

// Get subscription transactions
$subscriptions = Transaction::subscriptions()->get();

// Check transaction status
if ($transaction->isSuccessful()) {
    // Transaction completed
}
```

### PaymentToken

```php
use Sumit\LaravelPayment\Models\PaymentToken;

// Get user's tokens
$tokens = PaymentToken::where('user_id', auth()->id())
    ->active()
    ->get();

// Get default token
$defaultToken = PaymentToken::where('user_id', auth()->id())
    ->default()
    ->first();
```

### Customer

```php
use Sumit\LaravelPayment\Models\Customer;

// Find customer by SUMIT ID
$customer = Customer::findBySumitId($sumitCustomerId);

// Get or create customer for user
$customer = Customer::findOrCreateByUser(auth()->id(), [
    'email' => 'john@example.com',
    'name' => 'John Doe',
]);
```

## API Routes

The package registers the following routes:

### Payment Routes
- `POST /sumit/payment/process` - Process a payment
- `GET /sumit/payment/callback` - Handle redirect callback
- `GET /sumit/payment/{transactionId}` - Get transaction details

### Token Routes (Authenticated)
- `GET /sumit/tokens` - Get user's tokens
- `POST /sumit/tokens` - Create new token
- `PUT /sumit/tokens/{tokenId}/default` - Set token as default
- `DELETE /sumit/tokens/{tokenId}` - Delete token

## Advanced Usage

### Custom Payment Items

```php
$result = SumitPayment::processPayment([
    'amount' => 150.00,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'items' => [
        [
            'Name' => 'Product 1',
            'Price' => 100.00,
            'Quantity' => 1,
        ],
        [
            'Name' => 'Product 2',
            'Price' => 50.00,
            'Quantity' => 1,
        ],
    ],
    // ... card details
]);
```

### Installment Payments

```php
$result = SumitPayment::processPayment([
    'amount' => 1200.00,
    'payments_count' => 12, // 12 monthly payments
    // ... other payment data
]);
```

### Donation Receipts

```php
$result = SumitPayment::processPayment([
    'amount' => 100.00,
    'is_donation' => true,
    // ... other payment data
]);
```

## Testing

```bash
composer test
```

## Security

- Credit card data is never stored in your database
- PCI DSS compliant tokenization
- All API communication over HTTPS
- Sensitive data is excluded from logs

## Support

For support, email support@sumit.co.il or visit https://help.sumit.co.il

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [SUMIT](https://www.sumit.co.il)
- Converted from WooCommerce plugin to Laravel package
