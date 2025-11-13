# SUMIT Payment Gateway for Laravel

A comprehensive Laravel package for integrating SUMIT payment gateway with your Laravel 11+ application. This package provides secure payment processing, credit card tokenization, invoice generation, recurring billing capabilities, and full Filament Admin Panel integration.

## Features

- 💳 **Secure Payment Processing** - Process credit card payments with PCI compliance
- 🔐 **Token Storage** - Securely store customer payment methods for future use
- 📄 **Invoice Generation** - Automatically generate and email invoices/receipts
- 🔄 **Recurring Billing** - Support for subscription and recurring payments with automated charging
- 💰 **Refunds** - Process full or partial refunds through the API
- 🎯 **Events System** - Laravel events for payment lifecycle hooks
- 🔔 **Webhooks** - Handle payment status updates via webhooks
- 🛡️ **Security** - Built-in security features and encryption
- 🌐 **Multi-Currency** - Support for multiple currencies
- 📱 **Redirect & Direct** - Both redirect and direct payment flows
- ⚙️ **Filament Integration** - Complete admin panel with settings, transactions, and token management
- 🎨 **Spatie Settings** - Modern settings management with Laravel Spatie Settings

## Requirements

- PHP 8.1 or higher
- Laravel 11.x or 12.x
- SUMIT merchant account
- (Optional) Filament 4.x for admin panel integration

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

## Refunds

Process full or partial refunds:

```php
use Sumit\LaravelPayment\Services\RefundService;
use Sumit\LaravelPayment\Models\Transaction;

$refundService = app(RefundService::class);
$transaction = Transaction::find($transactionId);

// Full refund
$result = $refundService->processRefund($transaction);

// Partial refund
$result = $refundService->processRefund($transaction, 50.00, 'Customer requested partial refund');

if ($result['success']) {
    // Refund processed successfully
    $refundDocumentId = $result['refund_document_id'];
}
```

## Recurring Billing & Subscriptions

Create and manage recurring subscriptions:

```php
use Sumit\LaravelPayment\Services\RecurringBillingService;

$billingService = app(RecurringBillingService::class);

// Create a subscription
$result = $billingService->createSubscription([
    'user_id' => auth()->id(),
    'amount' => 99.00,
    'currency' => 'ILS',
    'frequency' => 'monthly', // daily, weekly, monthly, yearly
    'token_id' => $savedTokenId,
    'description' => 'Premium Membership',
    'charge_immediately' => true,
]);

if ($result['success']) {
    $subscription = $result['subscription'];
}

// Cancel a subscription
$result = $billingService->cancelSubscription($subscription);

// Update subscription
$result = $billingService->updateSubscription($subscription, [
    'amount' => 119.00,
    'frequency' => 'monthly',
]);

// Process due subscriptions (add to your scheduled tasks)
$results = $billingService->processDueSubscriptions();
```

### Automated Subscription Charging

Add to `app/Console/Kernel.php`:

```php
use Sumit\LaravelPayment\Services\RecurringBillingService;

protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(RecurringBillingService::class)->processDueSubscriptions();
    })->daily()->at('02:00');
}
```

## Webhooks

Handle payment status updates from SUMIT:

### Webhook URL

Configure your webhook URL in SUMIT dashboard to point to:
```
https://your-domain.com/sumit/webhook
```

### Listening to Webhook Events

```php
use Sumit\LaravelPayment\Events\WebhookReceived;
use Sumit\LaravelPayment\Events\PaymentStatusChanged;

// Listen for any webhook
Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    $eventType = $event->eventType;
    $data = $event->data;
    
    // Handle webhook
});

// Listen for payment status changes
Event::listen(PaymentStatusChanged::class, function (PaymentStatusChanged $event) {
    $transaction = $event->transaction;
    $newStatus = $event->newStatus;
    $oldStatus = $event->oldStatus;
    
    // Update your order status, send notifications, etc.
});
```

### Available Webhook Events

- `payment.completed` - Payment successfully processed
- `payment.failed` - Payment failed
- `payment.refunded` - Payment refunded
- `payment.authorized` - Payment authorized (J5 mode)
- `subscription.charged` - Subscription successfully charged
- `subscription.failed` - Subscription charge failed

## Filament Admin Panel Integration

This package includes full integration with Filament Admin Panel for managing payments, settings, and tokens.

### Quick Setup

1. Install Filament (if not already installed):
```bash
composer require filament/filament:"^4.1"
```

2. Register the plugin in your Filament Panel Provider:
```php
use Sumit\LaravelPayment\Filament\SumitPaymentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            SumitPaymentPlugin::make(),
        ]);
}
```

### Features

- **Settings Page** - Manage all payment gateway settings through a user-friendly interface
- **Transaction Resource** - View, filter, and manage all transactions
- **Payment Token Resource** - Manage saved payment methods
- **Real-time Updates** - Live status updates and filtering
- **Export Capabilities** - Export transaction data

For detailed Filament integration guide, see [FILAMENT_INTEGRATION.md](FILAMENT_INTEGRATION.md).

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
