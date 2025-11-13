# Installation Guide - Laravel OfficeGuy Package

## Prerequisites

Before installing the Laravel OfficeGuy package, ensure you have:

1. **Laravel Application**: Laravel 11.x or 12.x installed
2. **PHP Version**: PHP 8.1 or higher
3. **Database**: MySQL 5.7+ or PostgreSQL 9.6+
4. **SUMIT Account**: Active SUMIT account with API credentials
5. **Composer**: Latest version of Composer

## Step-by-Step Installation

### Step 1: Install Package

Add the package to your Laravel project:

```bash
composer require nm-digitalhub/laravel-officeguy
```

### Step 2: Publish Configuration

Publish the configuration file to your application:

```bash
php artisan vendor:publish --provider="NmDigitalHub\LaravelOfficeGuy\OfficeGuyServiceProvider" --tag="officeguy-config"
```

This creates `config/officeguy.php` in your application.

### Step 3: Publish and Run Migrations

Publish the migration files:

```bash
php artisan vendor:publish --provider="NmDigitalHub\LaravelOfficeGuy\OfficeGuyServiceProvider" --tag="officeguy-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

This creates four tables:
- `officeguy_payment_tokens`
- `officeguy_payments`
- `officeguy_customers`
- `officeguy_stock_sync_logs`

### Step 4: Configure Environment Variables

Add your SUMIT credentials to `.env`:

```env
# Required
OFFICEGUY_COMPANY_ID=12345
OFFICEGUY_PRIVATE_KEY=your-private-key-here
OFFICEGUY_PUBLIC_KEY=your-public-key-here

# Environment (www for production, dev for development)
OFFICEGUY_ENVIRONMENT=www

# Merchant Configuration
OFFICEGUY_MERCHANT_NUMBER=your-merchant-number

# Optional Settings
OFFICEGUY_SUBSCRIPTIONS_MERCHANT_NUMBER=your-subscription-merchant-number
OFFICEGUY_TESTING_MODE=false
OFFICEGUY_AUTHORIZE_ONLY=false
OFFICEGUY_DRAFT_DOCUMENT=false
OFFICEGUY_SEND_DOCUMENT_BY_EMAIL=true
OFFICEGUY_SUPPORT_TOKENS=true

# Payment Limits
OFFICEGUY_MAX_PAYMENTS=12
OFFICEGUY_MIN_AMOUNT_FOR_PAYMENTS=100
OFFICEGUY_MIN_AMOUNT_PER_PAYMENT=10

# Stock Sync
OFFICEGUY_STOCK_SYNC_ON_CHECKOUT=false
OFFICEGUY_STOCK_SYNC_FREQUENCY=none

# Logging
OFFICEGUY_LOGGING_ENABLED=true
OFFICEGUY_LOG_LEVEL=debug
```

### Step 5: Get Your API Credentials

1. Log in to your SUMIT account
2. Navigate to: https://app.sumit.co.il/developers/keys/
3. Copy your credentials:
   - Company ID
   - Private Key
   - Public Key
   - Merchant Number(s)

### Step 6: Test Configuration

Create a test route to verify the installation:

```php
// routes/web.php
use NmDigitalHub\LaravelOfficeGuy\Services\OfficeGuyApiService;

Route::get('/test-officeguy', function (OfficeGuyApiService $api) {
    $result = $api->checkCredentials();
    
    if ($result === null) {
        return 'Credentials are valid!';
    }
    
    return 'Error: ' . $result;
});
```

Visit `/test-officeguy` in your browser to verify the connection.

## Optional Configuration

### Custom Route Prefix

Change the API route prefix in `config/officeguy.php`:

```php
'routes' => [
    'prefix' => 'payment-gateway', // Default: 'officeguy'
    'middleware' => ['api'],
],
```

### Custom Logging

Configure logging channel:

```php
'logging' => [
    'enabled' => true,
    'channel' => 'daily', // or 'stack', 'single', etc.
    'level' => 'info',
],
```

### Webhook Configuration

If using webhooks, configure your callback URL in SUMIT dashboard and add to config:

```php
'routes' => [
    'webhook_middleware' => ['api', 'officeguy.verify'],
],
```

## Post-Installation Steps

### 1. Set Up Event Listeners

Create event listeners in `app/Providers/EventServiceProvider.php`:

```php
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed;
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentFailed;

protected $listen = [
    PaymentProcessed::class => [
        \App\Listeners\SendPaymentConfirmation::class,
    ],
    PaymentFailed::class => [
        \App\Listeners\NotifyAdminOfFailedPayment::class,
    ],
];
```

### 2. Configure Stock Sync Schedule (Optional)

If using stock synchronization, add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    if (config('officeguy.stock.sync_frequency') === '12') {
        $schedule->call(function () {
            app(\NmDigitalHub\LaravelOfficeGuy\Services\StockService::class)
                ->updateStock();
        })->twiceDaily();
    } elseif (config('officeguy.stock.sync_frequency') === '24') {
        $schedule->call(function () {
            app(\NmDigitalHub\LaravelOfficeGuy\Services\StockService::class)
                ->updateStock();
        })->daily();
    }
}
```

### 3. Create User Interface

Example payment form:

```blade
<!-- resources/views/payment/form.blade.php -->
<form id="payment-form" method="POST" action="{{ route('payment.process') }}">
    @csrf
    
    <div class="form-group">
        <label>Amount</label>
        <input type="number" name="amount" step="0.01" required>
    </div>
    
    <div class="form-group">
        <label>Card Token</label>
        <input type="text" name="single_use_token" required>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="save_token" value="1">
            Save card for future use
        </label>
    </div>
    
    <button type="submit">Pay Now</button>
</form>
```

## Troubleshooting

### Common Issues

**Issue**: "Table not found" error
**Solution**: Run `php artisan migrate`

**Issue**: "Invalid credentials" error
**Solution**: Verify credentials in SUMIT dashboard and `.env` file

**Issue**: "Route not found" error
**Solution**: Clear route cache with `php artisan route:clear`

**Issue**: Payment failing silently
**Solution**: Check logs in `storage/logs/laravel.log`

### Debug Mode

Enable detailed logging:

```env
OFFICEGUY_LOG_LEVEL=debug
```

### Testing Environment

For testing, use the development environment:

```env
OFFICEGUY_ENVIRONMENT=dev
OFFICEGUY_TESTING_MODE=true
```

## Next Steps

1. Read the [Usage Examples](USAGE.md)
2. Review the [API Documentation](API.md)
3. Check the [WooCommerce Migration Guide](MIGRATION.md)
4. Set up [Event Listeners](EVENTS.md)

## Support

If you encounter issues during installation:

1. Check the [FAQ](FAQ.md)
2. Search existing [GitHub Issues](https://github.com/nm-digitalhub/laravel-officeguy/issues)
3. Contact support: info@nm-digitalhub.com
4. SUMIT technical support: https://help.sumit.co.il
