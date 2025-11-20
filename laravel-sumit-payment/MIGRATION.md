# Migration Guide: WooCommerce to Laravel

This guide helps you migrate from the WooCommerce SUMIT Payment Gateway plugin to the Laravel SUMIT Payment package.

## Table of Contents

1. [Overview](#overview)
2. [Key Differences](#key-differences)
3. [Installation](#installation)
4. [Configuration Migration](#configuration-migration)
5. [Code Migration](#code-migration)
6. [Data Migration](#data-migration)
7. [Hooks Migration](#hooks-migration)
8. [Testing](#testing)

## Overview

The Laravel SUMIT Payment package is a complete rewrite of the WooCommerce plugin, designed to work natively with Laravel's architecture while maintaining all core functionality.

## Key Differences

### Architecture

| Aspect | WooCommerce | Laravel |
|--------|-------------|---------|
| Payment Processing | WordPress hooks | Service classes |
| Data Storage | WordPress post meta | Database tables with migrations |
| Configuration | WordPress options | Laravel config files |
| Events | WordPress actions/filters | Laravel events |
| API Integration | WP HTTP API | Guzzle HTTP client |
| Admin Interface | WordPress admin pages | RESTful API endpoints |

### File Structure

**WooCommerce:**
```
woo-payment-gateway-officeguy/
├── includes/
│   ├── OfficeGuyPayment.php
│   ├── OfficeGuyAPI.php
│   ├── OfficeGuyTokens.php
│   └── ...
└── officeguy-woo.php
```

**Laravel:**
```
laravel-sumit-payment/
├── src/
│   ├── Services/
│   │   ├── PaymentService.php
│   │   ├── SumitApiService.php
│   │   └── TokenService.php
│   ├── Models/
│   ├── Controllers/
│   ├── Events/
│   └── ...
└── composer.json
```

## Installation

### 1. Install Package

```bash
composer require nm-digitalhub/laravel-sumit-payment
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

### 3. Run Migrations

```bash
php artisan migrate
```

## Configuration Migration

### WooCommerce Settings → Laravel Config

Map your WooCommerce settings to Laravel configuration:

**WooCommerce (WordPress Admin):**
- Company ID → `SUMIT_COMPANY_ID` in `.env`
- API Key → `SUMIT_API_KEY` in `.env`
- Merchant Number → `SUMIT_MERCHANT_NUMBER` in `.env`

**Example `.env` configuration:**

```env
# From WooCommerce plugin settings
SUMIT_COMPANY_ID=12345
SUMIT_API_KEY=your-api-key-here
SUMIT_API_PUBLIC_KEY=your-public-key
SUMIT_ENVIRONMENT=www
SUMIT_MERCHANT_NUMBER=1234567
SUMIT_SUBSCRIPTION_MERCHANT_NUMBER=7654321

# Payment settings
SUMIT_PCI_COMPLIANCE=no
SUMIT_TOKEN_PARAM=J5
SUMIT_SUPPORT_TOKENS=true
SUMIT_MAX_PAYMENTS=12

# Document settings
SUMIT_DRAFT_DOCUMENT=false
SUMIT_EMAIL_DOCUMENT=true
SUMIT_CREATE_ORDER_DOCUMENT=false

# Other settings
SUMIT_MERGE_CUSTOMERS=true
SUMIT_LOGGING=true
```

### Settings Comparison Table

| WooCommerce Setting | Laravel Config Key |
|--------------------|--------------------|
| Company ID | `sumit-payment.company_id` |
| API Private Key | `sumit-payment.api_key` |
| API Public Key | `sumit-payment.api_public_key` |
| Environment | `sumit-payment.environment` |
| Testing Mode | `sumit-payment.testing_mode` |
| Merchant Number | `sumit-payment.merchant_number` |
| Max Payments | `sumit-payment.max_payments` |
| Draft Document | `sumit-payment.draft_document` |
| Email Document | `sumit-payment.email_document` |
| Merge Customers | `sumit-payment.merge_customers` |
| PCI Compliance | `sumit-payment.pci_compliance` |
| Token Parameter | `sumit-payment.token_param` |

## Code Migration

### Payment Processing

**WooCommerce:**
```php
// In WooCommerce gateway class
public function process_payment($order_id) {
    $order = wc_get_order($order_id);
    $result = OfficeGuyPayment::ProcessOrder($this, $order, false);
    return $result;
}
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Facades\SumitPayment;

public function processPayment(Request $request) {
    $result = SumitPayment::processPayment([
        'order_id' => $request->order_id,
        'user_id' => auth()->id(),
        'amount' => $request->amount,
        'currency' => $request->currency,
        'items' => $request->items,
        'customer' => $request->customer,
    ]);
    
    return $result['success'] 
        ? redirect()->route('payment.success')
        : back()->withErrors(['payment' => $result['error']]);
}
```

### Token Management

**WooCommerce:**
```php
// Get customer tokens
$tokens = WC_Payment_Tokens::get_customer_tokens($user_id);

// Save token
$token = new WC_Payment_Token_CC();
$token->set_token($card_token);
$token->set_user_id($user_id);
$token->save();
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Services\TokenService;

// Get customer tokens
$tokenService = app(TokenService::class);
$tokens = $tokenService->getUserTokens(auth()->id());

// Save token
$tokenService->createToken([
    'user_id' => auth()->id(),
    'token' => $card_token,
    'last_four' => $last_four,
    'expiry_month' => $month,
    'expiry_year' => $year,
]);
```

### Invoice Creation

**WooCommerce:**
```php
$result = OfficeGuyPayment::CreateDocumentOnPaymentCompleteInternal($order_id, true);
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);
$result = $invoiceService->createInvoice([
    'order_id' => $order_id,
    'type' => 'invoice',
    'items' => $items,
    'customer' => $customer,
    'total_amount' => $amount,
]);
```

## Data Migration

### Migrating Payment Tokens

If you need to migrate existing payment tokens from WooCommerce:

```php
use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;

// Example migration script
public function migrateTokens() {
    global $wpdb;
    
    // Get WooCommerce tokens
    $wc_tokens = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens
        WHERE gateway_id = 'officeguy'
    ");
    
    foreach ($wc_tokens as $wc_token) {
        PaymentToken::create([
            'user_id' => $wc_token->user_id,
            'token' => $wc_token->token,
            'last_four' => get_metadata('payment_token', $wc_token->token_id, 'last4', true),
            'expiry_month' => get_metadata('payment_token', $wc_token->token_id, 'expiry_month', true),
            'expiry_year' => get_metadata('payment_token', $wc_token->token_id, 'expiry_year', true),
            'is_default' => $wc_token->is_default,
        ]);
    }
}
```

### Migrating Transaction Data

```php
use NmDigitalHub\LaravelSumitPayment\Models\Transaction;

// Example migration script
public function migrateTransactions() {
    // Get WooCommerce orders with OfficeGuy metadata
    $orders = wc_get_orders([
        'payment_method' => 'officeguy',
        'limit' => -1,
    ]);
    
    foreach ($orders as $order) {
        Transaction::create([
            'user_id' => $order->get_customer_id(),
            'order_id' => $order->get_id(),
            'payment_id' => $order->get_meta('OfficeGuyPaymentID'),
            'auth_number' => $order->get_meta('OfficeGuyAuthNumber'),
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => 'completed',
            'valid_payment' => true,
            'document_id' => $order->get_meta('OfficeGuyDocumentID'),
            'customer_id' => $order->get_meta('OfficeGuyCustomerID'),
            'created_at' => $order->get_date_created(),
        ]);
    }
}
```

## Hooks Migration

### Filter Hooks

**WooCommerce:**
```php
add_filter('sumit_maximum_installments', 'custom_installments', 10, 2);

function custom_installments($max, $order_value) {
    if ($order_value > 1000) {
        return 24;
    }
    return 5;
}
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments;

// In EventServiceProvider
protected $listen = [
    MaximumInstallments::class => [
        CustomInstallmentsListener::class,
    ],
];

// In listener
class CustomInstallmentsListener
{
    public function handle(MaximumInstallments $event)
    {
        if ($event->orderValue > 1000) {
            $event->setMaxPayments(24);
        } else {
            $event->setMaxPayments(5);
        }
    }
}
```

### Customer Fields Hook

**WooCommerce:**
```php
add_filter('sumit_customer_fields', 'custom_customer_fields', 10, 2);

function custom_customer_fields($customer, $order) {
    $customer['BillingLastName'] = $order->get_billing_last_name();
    return $customer;
}
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields;

Event::listen(CustomerFields::class, function ($event) {
    $event->setField('BillingLastName', $event->orderData['billing_last_name'] ?? '');
});
```

### Item Fields Hook

**WooCommerce:**
```php
add_filter('sumit_item_fields', 'custom_item_fields', 10, 5);

function custom_item_fields($item, $product, $unit_price, $order_item, $order) {
    $item['Name'] .= ' - ' . $product->get_sku();
    
    if ($unit_price == 0) {
        return null;
    }
    
    return $item;
}
```

**Laravel:**
```php
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\ItemFields;

Event::listen(ItemFields::class, function ($event) {
    if (!empty($event->product['sku'])) {
        $event->setField('Name', $event->item['Name'] . ' - ' . $event->product['sku']);
    }
    
    if ($event->unitPrice == 0) {
        $event->removeItem();
    }
});
```

## Testing

### Testing Payment Processing

**WooCommerce:**
```php
// Usually manual testing through WordPress
```

**Laravel:**
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use NmDigitalHub\LaravelSumitPayment\Facades\SumitPayment;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_payment()
    {
        $result = SumitPayment::processPayment([
            'order_id' => 'TEST-001',
            'amount' => 100.00,
            'currency' => 'ILS',
            // ... other fields
        ]);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('sumit_transactions', [
            'order_id' => 'TEST-001',
            'status' => 'completed',
        ]);
    }
}
```

## Common Migration Issues

### Issue: Missing WooCommerce Functions

**Problem:** Code relies on WooCommerce functions like `wc_get_order()`

**Solution:** Replace with Laravel equivalents:
```php
// Instead of:
$order = wc_get_order($order_id);

// Use your Order model:
$order = Order::find($order_id);
```

### Issue: WordPress User System

**Problem:** Code uses WordPress user functions

**Solution:** Use Laravel's authentication:
```php
// Instead of:
$user_id = get_current_user_id();

// Use:
$user_id = auth()->id();
```

### Issue: WordPress Options

**Problem:** Code uses `get_option()` and `update_option()`

**Solution:** Use Laravel's config or database:
```php
// Instead of:
$value = get_option('sumit_setting');

// Use:
$value = config('sumit-payment.setting');
```

## Support

For migration assistance:
- Review the [EXAMPLES.md](EXAMPLES.md) file for code examples
- Check [HOOKS.md](HOOKS.md) for custom hooks documentation
- Open an issue on GitHub for specific migration questions

## Checklist

- [ ] Install Laravel package
- [ ] Migrate configuration to `.env` and config files
- [ ] Update payment processing code
- [ ] Migrate custom hooks to Laravel events
- [ ] Migrate payment tokens (if needed)
- [ ] Migrate transaction history (if needed)
- [ ] Test payment flow
- [ ] Test webhook handling
- [ ] Test refund processing
- [ ] Update documentation
- [ ] Deploy to production
