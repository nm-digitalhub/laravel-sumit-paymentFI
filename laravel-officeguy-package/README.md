# Laravel OfficeGuy Payment Gateway Package

A comprehensive Laravel package for integrating SUMIT (OfficeGuy) payment gateway with invoice creation, credit card token storage, and secure transaction processing. This package is a complete conversion of the WooCommerce plugin to a Laravel 12+ compatible package.

> **🚀 Quick Start**: See [QUICKSTART.md](QUICKSTART.md) for a 5-minute setup guide.

## Features

- 🔒 **Secure Payment Processing** - PCI-compliant credit card processing
- 💳 **Token Management** - Store and manage payment tokens securely
- 📄 **Invoice Generation** - Automatic invoice/receipt creation
- 🔄 **Subscription Support** - Recurring payment handling
- 📦 **Stock Synchronization** - Inventory sync with SUMIT
- 🎯 **Event System** - Laravel events for payment lifecycle
- 🔗 **Webhook Support** - Handle payment callbacks
- 🌐 **Multi-currency** - Support for ILS, USD, EUR, GBP
- 📊 **Payment Tracking** - Complete payment history and logging

## Requirements

- PHP 8.1 or higher
- Laravel 11.x or 12.x
- MySQL/PostgreSQL database
- SUMIT account with API credentials

## Installation

### 1. Install via Composer

```bash
composer require nm-digitalhub/laravel-officeguy
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=officeguy-config
```

This will create a `config/officeguy.php` file where you can customize the package settings.

### 3. Publish Migrations

```bash
php artisan vendor:publish --tag=officeguy-migrations
```

### 4. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `officeguy_payment_tokens` - Stores payment tokens
- `officeguy_payments` - Payment transaction records
- `officeguy_customers` - Customer information
- `officeguy_stock_sync_logs` - Stock synchronization logs

### 5. Configure Environment Variables

Add the following to your `.env` file:

```env
OFFICEGUY_COMPANY_ID=your_company_id
OFFICEGUY_PRIVATE_KEY=your_private_key
OFFICEGUY_PUBLIC_KEY=your_public_key
OFFICEGUY_ENVIRONMENT=www
OFFICEGUY_MERCHANT_NUMBER=your_merchant_number

# Optional settings
OFFICEGUY_TESTING_MODE=false
OFFICEGUY_AUTHORIZE_ONLY=false
OFFICEGUY_DRAFT_DOCUMENT=false
OFFICEGUY_SEND_DOCUMENT_BY_EMAIL=true
```

You can find your API credentials at: https://app.sumit.co.il/developers/keys/

## Configuration

The package configuration file (`config/officeguy.php`) contains all available settings:

- **Credentials** - Company ID, API keys
- **Environment** - Production or development
- **Payment Settings** - Merchant numbers, authorization settings
- **Payment Limits** - Maximum payments, minimum amounts
- **Customer Settings** - Customer merge options
- **Document Settings** - Language, VAT settings
- **Token Settings** - Token storage configuration
- **Stock Settings** - Inventory synchronization
- **Logging** - Log channel and level
- **Routes** - API endpoint configuration

## Usage

### Processing a Payment

```php
use NmDigitalHub\LaravelOfficeGuy\Services\PaymentService;

class CheckoutController extends Controller
{
    public function processPayment(PaymentService $paymentService)
    {
        $result = $paymentService->processPayment([
            'amount' => 100.00,
            'currency' => 'ILS',
            'user_id' => auth()->id(),
            'order_id' => 12345,
            'description' => 'Order #12345',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '0501234567',
                'address' => '123 Main St',
                'city' => 'Tel Aviv',
            ],
            'single_use_token' => $request->input('payment_token'),
            'save_token' => true,
        ]);

        if ($result['success']) {
            return redirect()->route('payment.success')
                ->with('payment_id', $result['payment']->id);
        }

        return back()->with('error', $result['error']);
    }
}
```

### Creating a Payment Token

```php
use NmDigitalHub\LaravelOfficeGuy\Services\TokenService;

class PaymentMethodController extends Controller
{
    public function addCard(TokenService $tokenService, Request $request)
    {
        $result = $tokenService->createToken([
            'user_id' => auth()->id(),
            'single_use_token' => $request->input('token'),
            'set_as_default' => true,
        ]);

        if ($result['success']) {
            return response()->json([
                'message' => 'Card added successfully',
                'token' => $result['token'],
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
    }
}
```

### Subscription Payment

```php
use NmDigitalHub\LaravelOfficeGuy\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    public function processSubscription(SubscriptionService $subscriptionService)
    {
        $result = $subscriptionService->createSubscription([
            'user_id' => auth()->id(),
            'amount' => 99.00,
            'currency' => 'ILS',
            'description' => 'Monthly Subscription',
            'customer' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'single_use_token' => $request->input('token'),
        ]);

        if ($result['success']) {
            // Store subscription details
            return redirect()->route('subscription.success');
        }

        return back()->with('error', $result['error']);
    }
}
```

### Stock Synchronization

```php
use NmDigitalHub\LaravelOfficeGuy\Services\StockService;

class StockController extends Controller
{
    public function syncStock(StockService $stockService)
    {
        $result = $stockService->updateStock(forceSync: true);

        return response()->json($result);
    }
}
```

### Using the Facade

```php
use NmDigitalHub\LaravelOfficeGuy\Facades\OfficeGuy;

// Process payment
$result = OfficeGuy::processPayment($paymentData);

// Create token
$result = OfficeGuy::createToken($tokenData);

// Update stock
$result = OfficeGuy::updateStock();
```

## Events

The package dispatches the following events:

### PaymentProcessed

Fired when a payment is successfully processed.

```php
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed;

Event::listen(PaymentProcessed::class, function ($event) {
    // $event->payment - Payment model
    // $event->response - API response
    
    // Send confirmation email, update order, etc.
});
```

### PaymentFailed

Fired when a payment fails.

```php
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function ($event) {
    // $event->payment - Payment model
    // $event->response - API response
    
    // Log failure, notify admin, etc.
});
```

### TokenCreated

Fired when a payment token is created.

```php
use NmDigitalHub\LaravelOfficeGuy\Events\TokenCreated;

Event::listen(TokenCreated::class, function ($event) {
    // $event->token - PaymentToken model
    
    // Update user preferences, send notification, etc.
});
```

### StockSynced

Fired when stock synchronization completes.

```php
use NmDigitalHub\LaravelOfficeGuy\Events\StockSynced;

Event::listen(StockSynced::class, function ($event) {
    // $event->updatedCount - Number of products updated
    // $event->failedCount - Number of failed updates
    
    // Log results, notify admin, etc.
});
```

## API Routes

The package registers the following API routes (prefix: `/api/officeguy`):

### Payment Routes
- `POST /payments` - Process a payment
- `GET /payments` - List user payments
- `GET /payments/{id}` - Get payment details
- `POST /payments/{id}/refund` - Refund a payment

### Token Routes
- `POST /tokens` - Create a payment token
- `GET /tokens` - List user tokens
- `DELETE /tokens/{id}` - Delete a token
- `POST /tokens/{id}/set-default` - Set token as default

### Stock Routes
- `POST /stock/sync` - Trigger stock synchronization
- `GET /stock/logs` - Get sync logs
- `GET /stock/status` - Get sync status

### Webhook Routes
- `POST /webhook` - Handle payment webhooks
- `GET /redirect` - Handle payment redirects

## Models

### Payment

```php
use NmDigitalHub\LaravelOfficeGuy\Models\Payment;

// Find payment
$payment = Payment::find($id);

// Check status
if ($payment->isSuccessful()) {
    // Payment completed
}

// Get payments
$payments = Payment::successful()->get();
$subscriptionPayments = Payment::subscription()->get();
```

### PaymentToken

```php
use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;

// Get user tokens
$tokens = PaymentToken::forUser($userId)->get();

// Get default token
$defaultToken = PaymentToken::forUser($userId)->default()->first();

// Check expiry
if ($token->isExpired()) {
    // Token expired
}
```

### Customer

```php
use NmDigitalHub\LaravelOfficeGuy\Models\Customer;

// Find by email
$customer = Customer::byEmail('john@example.com')->first();

// Get full address
$address = $customer->full_address;
```

## Migration from WooCommerce Plugin

If you're migrating from the WooCommerce plugin, here's a mapping of key components:

| WooCommerce | Laravel Package |
|-------------|----------------|
| `OfficeGuyAPI` | `OfficeGuyApiService` |
| `OfficeGuyPayment` | `PaymentService` |
| `OfficeGuyTokens` | `TokenService` |
| `OfficeGuyStock` | `StockService` |
| `OfficeGuySubscriptions` | `SubscriptionService` |
| WooCommerce Hooks | Laravel Events |
| Gateway Settings | Config File |
| Payment Meta | Payment Model |
| Token Meta | PaymentToken Model |

## Testing

Run the package tests:

```bash
composer test
```

## Security

- Never store raw credit card data
- All card data is tokenized via SUMIT's API
- Use HTTPS in production
- Validate webhook signatures
- Log all payment attempts
- Regular security audits recommended

## Support

For support and bug reports:
- GitHub Issues: https://github.com/nm-digitalhub/laravel-officeguy
- Email: info@nm-digitalhub.com
- SUMIT Support: https://help.sumit.co.il

## License

MIT License. See LICENSE file for details.

## Credits

Developed by NM Digital Hub
Based on the WooCommerce OfficeGuy Plugin
