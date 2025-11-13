# Installation and Integration Guide

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Configuration](#configuration)
4. [Database Setup](#database-setup)
5. [Basic Integration](#basic-integration)
6. [Advanced Integration](#advanced-integration)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

## System Requirements

- PHP 8.1 or higher
- Laravel 11.x or 12.x
- MySQL 5.7+ / PostgreSQL 9.6+ / SQLite 3.8.8+
- SUMIT merchant account with API credentials
- Composer
- SSL certificate (required for production)

## Installation Steps

### Step 1: Install via Composer

```bash
composer require sumit/laravel-payment-gateway
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

This will create `config/sumit-payment.php` in your application.

### Step 3: Publish Migrations

```bash
php artisan vendor:publish --tag=sumit-payment-migrations
```

### Step 4: Run Migrations

```bash
php artisan migrate
```

This will create three tables:
- `sumit_payment_tokens` - Stores payment tokens
- `sumit_transactions` - Stores transaction history
- `sumit_customers` - Stores customer information

### Step 5: (Optional) Publish Views

If you want to customize the payment forms:

```bash
php artisan vendor:publish --tag=sumit-payment-views
```

Views will be published to `resources/views/vendor/sumit-payment/`

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# Required Settings
SUMIT_COMPANY_ID=your-company-id
SUMIT_API_KEY=your-api-key
SUMIT_API_PUBLIC_KEY=your-public-key
SUMIT_MERCHANT_NUMBER=your-merchant-number

# Environment (www for production, dev for development)
SUMIT_ENVIRONMENT=www

# Optional Settings
SUMIT_TESTING_MODE=false
SUMIT_PCI_MODE=direct
SUMIT_EMAIL_DOCUMENT=true
SUMIT_DRAFT_DOCUMENT=false
SUMIT_DOCUMENT_LANGUAGE=he
SUMIT_MAXIMUM_PAYMENTS=12
SUMIT_AUTHORIZE_ONLY=false
SUMIT_AUTO_CAPTURE=true
SUMIT_VAT_INCLUDED=true
SUMIT_DEFAULT_VAT_RATE=17
SUMIT_LOGGING_ENABLED=true
SUMIT_SEND_CLIENT_IP=true

# For subscriptions (if different from regular payments)
SUMIT_SUBSCRIPTIONS_MERCHANT_NUMBER=your-subscriptions-merchant-number

# Token method (J2 or J5)
SUMIT_TOKEN_METHOD=J2

# Authorization settings (for J5)
SUMIT_AUTHORIZE_ADDED_PERCENT=0
SUMIT_AUTHORIZE_MINIMUM_ADDITION=0

# API settings
SUMIT_API_TIMEOUT=180
SUMIT_LOG_CHANNEL=stack

# Routes
SUMIT_CALLBACK_URL=/sumit/callback
```

### Obtaining SUMIT Credentials

1. Log in to your SUMIT merchant account at https://app.sumit.co.il
2. Navigate to Settings > API Integration
3. Generate or copy your:
   - Company ID
   - API Key
   - API Public Key
   - Merchant Number

## Database Setup

The package automatically creates the necessary tables. Here's what each table stores:

### sumit_payment_tokens
Stores encrypted payment tokens for recurring payments:
- Token reference
- Last 4 digits
- Expiry date
- Default flag
- User association

### sumit_transactions
Stores all payment transactions:
- Transaction ID
- Amount and currency
- Status (pending, completed, failed, etc.)
- Payment method details
- Document references
- Metadata

### sumit_customers
Stores SUMIT customer records:
- SUMIT customer ID
- User association
- Contact information
- Address details
- Custom metadata

## Basic Integration

### Example 1: Simple Payment Processing

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sumit\LaravelPayment\Facades\SumitPayment;

class PaymentController extends Controller
{
    public function showPaymentForm()
    {
        return view('checkout');
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'card_number' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string',
            'cvv' => 'required|string|size:3',
        ]);

        $result = SumitPayment::processPayment($validated);

        if ($result['success']) {
            return redirect()->route('payment.success')
                ->with('transaction', $result['transaction']);
        }

        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

### Example 2: E-commerce Integration

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Sumit\LaravelPayment\Facades\SumitPayment;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'total' => $request->total,
            'status' => 'pending',
        ]);

        // Process payment
        $result = SumitPayment::processPayment([
            'amount' => $order->total,
            'currency' => 'ILS',
            'order_id' => $order->id,
            'customer_name' => auth()->user()->name,
            'customer_email' => auth()->user()->email,
            'customer_phone' => $request->phone,
            'card_number' => $request->card_number,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'cvv' => $request->cvv,
            'description' => "Order #{$order->id}",
            'items' => $order->items->map(function ($item) {
                return [
                    'Name' => $item->product->name,
                    'Price' => $item->price,
                    'Quantity' => $item->quantity,
                ];
            })->toArray(),
        ]);

        if ($result['success']) {
            $order->update([
                'status' => 'paid',
                'transaction_id' => $result['transaction']->id,
            ]);

            return redirect()->route('order.success', $order);
        }

        $order->update(['status' => 'failed']);
        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

### Example 3: Using Event Listeners

Create a listener in `app/Listeners/SendPaymentConfirmation.php`:

```php
<?php

namespace App\Listeners;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Mail\PaymentConfirmationMail;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmation
{
    public function handle(PaymentCompleted $event)
    {
        $transaction = $event->transaction;
        
        // Send confirmation email
        Mail::to($transaction->metadata['customer_email'])
            ->send(new PaymentConfirmationMail($transaction));
            
        // Update order status
        if ($transaction->order_id) {
            Order::find($transaction->order_id)
                ->update(['status' => 'paid']);
        }
    }
}
```

Register the listener in `app/Providers/EventServiceProvider.php`:

```php
use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Listeners\SendPaymentConfirmation;

protected $listen = [
    PaymentCompleted::class => [
        SendPaymentConfirmation::class,
    ],
];
```

## Advanced Integration

### Token Management

```php
// Save a card
use Sumit\LaravelPayment\Services\TokenService;

public function saveCard(Request $request, TokenService $tokenService)
{
    $result = SumitPayment::tokenizeCard([
        'card_number' => $request->card_number,
        'expiry_month' => $request->expiry_month,
        'expiry_year' => $request->expiry_year,
        'cvv' => $request->cvv,
        'cardholder_name' => $request->cardholder_name,
        'is_default' => true,
    ], auth()->id());

    if ($result['success']) {
        return response()->json([
            'message' => 'Card saved successfully',
            'token_id' => $result['token_id'],
        ]);
    }

    return response()->json(['error' => $result['message']], 400);
}

// Pay with saved token
public function payWithToken(Request $request)
{
    $result = SumitPayment::processPaymentWithToken([
        'amount' => $request->amount,
        'customer_name' => auth()->user()->name,
        'customer_email' => auth()->user()->email,
    ], $request->token_id);

    // Handle result...
}
```

### Subscription Payments

```php
// Process subscription payment
$result = SumitPayment::processPayment([
    'amount' => 99.00,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'is_subscription' => true,
    'payments_count' => 12, // Monthly for 12 months
    'card_number' => '4580000000000000',
    'expiry_month' => '12',
    'expiry_year' => '25',
    'cvv' => '123',
]);
```

### Custom Service Extension

Create a custom service in `app/Services/CustomPaymentService.php`:

```php
<?php

namespace App\Services;

use Sumit\LaravelPayment\Services\PaymentService;

class CustomPaymentService extends PaymentService
{
    protected function getMaximumPayments(float $amount): int
    {
        // Custom logic: Higher amounts get more installments
        if ($amount > 1000) {
            return 24;
        } elseif ($amount > 500) {
            return 12;
        }
        
        return 6;
    }

    protected function buildCustomer(array $paymentData): array
    {
        $customer = parent::buildCustomer($paymentData);
        
        // Add custom fields
        if (isset($paymentData['customer_tax_id'])) {
            $customer['TaxID'] = $paymentData['customer_tax_id'];
        }
        
        return $customer;
    }
}
```

Register in `app/Providers/AppServiceProvider.php`:

```php
use App\Services\CustomPaymentService;
use Sumit\LaravelPayment\Services\PaymentService;

public function register()
{
    $this->app->singleton(PaymentService::class, function ($app) {
        return new CustomPaymentService(
            $app->make(\Sumit\LaravelPayment\Services\ApiService::class),
            $app->make(\Sumit\LaravelPayment\Services\TokenService::class)
        );
    });
}
```

## Testing

### Test Mode

Enable test mode in `.env`:

```env
SUMIT_TESTING_MODE=true
```

This will process payments as authorization-only (no actual charge).

### Unit Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Sumit\LaravelPayment\Facades\SumitPayment;

class PaymentTest extends TestCase
{
    public function test_successful_payment()
    {
        $result = SumitPayment::processPayment([
            'amount' => 100.00,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'card_number' => '4580000000000000',
            'expiry_month' => '12',
            'expiry_year' => '25',
            'cvv' => '123',
        ]);

        // Assertions based on test environment response
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('transaction', $result);
    }
}
```

## Troubleshooting

### Common Issues

#### 1. "Payment gateway is not properly configured"

**Solution:** Verify all credentials in `.env`:
```bash
php artisan config:cache
```

#### 2. "No response from payment gateway"

**Solution:** Check:
- Internet connection
- API endpoint accessibility
- Firewall settings
- SSL certificate validity

#### 3. "Invalid credentials"

**Solution:** 
- Verify Company ID and API Key
- Check environment setting (www vs dev)
- Ensure credentials match the environment

#### 4. Database errors

**Solution:**
```bash
php artisan migrate:fresh
php artisan config:cache
```

#### 5. Token not found

**Solution:** Ensure user authentication and token ownership:
```php
$token = PaymentToken::where('user_id', auth()->id())
    ->find($tokenId);
```

### Logging

Enable detailed logging:

```env
SUMIT_LOGGING_ENABLED=true
```

Check logs at `storage/logs/laravel.log` for detailed API communication.

### Support

For technical support:
- Email: support@sumit.co.il
- Documentation: https://help.sumit.co.il
- GitHub Issues: [Create an issue]

## Security Best Practices

1. **Always use HTTPS in production**
2. **Never log sensitive card data**
3. **Validate all inputs**
4. **Use environment variables for credentials**
5. **Regularly update the package**
6. **Implement rate limiting on payment endpoints**
7. **Monitor transaction logs for suspicious activity**
8. **Use CSRF protection on forms**
9. **Implement strong authentication for saved tokens**
10. **Regularly clean up expired tokens**

## Performance Optimization

### Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
```

### Queue Event Listeners

```php
class SendPaymentConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    // Implementation...
}
```

### Database Indexing

Indexes are already created on:
- `user_id`
- `transaction_id`
- `status`
- `created_at`
- `type`
- `payment_token_id`

For additional performance, consider archiving old transactions.

## Filament Admin Panel Integration

### Installation

If you want to use the Filament admin panel integration:

1. **Install Filament** (if not already installed):

```bash
composer require filament/filament:"^4.1"
php artisan filament:install --panels
```

2. **Register the SUMIT Payment Plugin** in your Panel Provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Sumit\LaravelPayment\Filament\SumitPaymentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->plugins([
            SumitPaymentPlugin::make(),
        ]);
}
```

3. **Run migrations** to create the settings table:

```bash
php artisan migrate
```

### Features Available

Once installed, you'll have access to:

1. **Payment Settings Page** - Manage all payment gateway configuration
   - API credentials
   - Environment settings
   - Payment options
   - Document settings
   - VAT configuration

2. **Transaction Resource** - View and manage all transactions
   - Filter by status, type, date
   - View transaction details
   - Export data
   - Track refunds

3. **Payment Token Resource** - Manage saved payment methods
   - View all saved cards
   - Edit default payment methods
   - Delete expired tokens

### Accessing the Admin Panel

After installation, access the admin panel at:
```
https://your-domain.com/admin
```

Navigate to **Payment Gateway** section to manage payments.

For detailed Filament integration instructions, see [FILAMENT_INTEGRATION.md](FILAMENT_INTEGRATION.md).

## Webhooks Configuration

### Setting Up Webhooks

1. **Configure Webhook URL** in your SUMIT merchant dashboard:
   ```
   https://your-domain.com/sumit/webhook
   ```

2. **Listen to Webhook Events** in your `EventServiceProvider`:

```php
use Sumit\LaravelPayment\Events\PaymentStatusChanged;
use Sumit\LaravelPayment\Events\WebhookReceived;

protected $listen = [
    PaymentStatusChanged::class => [
        \App\Listeners\UpdateOrderStatus::class,
    ],
    WebhookReceived::class => [
        \App\Listeners\LogWebhookActivity::class,
    ],
];
```

3. **Create Event Listeners**:

```bash
php artisan make:listener UpdateOrderStatus
php artisan make:listener LogWebhookActivity
```

### Testing Webhooks Locally

Use a tool like ngrok for local webhook testing:

```bash
ngrok http 8000
```

Then use the ngrok URL in your SUMIT webhook configuration.

## Scheduled Tasks for Subscriptions

To automatically charge recurring subscriptions, add to `app/Console/Kernel.php`:

```php
use Sumit\LaravelPayment\Services\RecurringBillingService;

protected function schedule(Schedule $schedule)
{
    // Process subscriptions daily at 2 AM
    $schedule->call(function () {
        app(RecurringBillingService::class)->processDueSubscriptions();
    })->daily()->at('02:00');
}
```

Make sure your scheduler is running:

```bash
# Add to crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Next Steps

1. Customize the configuration in `config/sumit-payment.php`
2. Create event listeners for your business logic
3. Customize the views if needed
4. Set up monitoring and alerts
5. Test thoroughly in test mode before going live
6. Configure rate limiting and security measures
7. Set up backup and recovery procedures

## Additional Resources

- [SUMIT API Documentation](https://help.sumit.co.il)
- [Laravel Events Documentation](https://laravel.com/docs/events)
- [Laravel Validation Documentation](https://laravel.com/docs/validation)
- [PCI DSS Compliance Guide](https://www.pcisecuritystandards.org/)
