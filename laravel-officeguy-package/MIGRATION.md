# Migration Guide: WooCommerce to Laravel

This guide helps you migrate from the WooCommerce OfficeGuy plugin to the Laravel package.

## Overview

The Laravel package is a complete rewrite of the WooCommerce plugin, designed to work natively with Laravel's architecture while maintaining all core functionality.

## Architecture Changes

### WooCommerce Plugin → Laravel Package

| Component | WooCommerce | Laravel Package |
|-----------|-------------|-----------------|
| **API Client** | `OfficeGuyAPI` class | `OfficeGuyApiService` |
| **Payment Processing** | `OfficeGuyPayment` class | `PaymentService` |
| **Token Management** | `OfficeGuyTokens` class | `TokenService` |
| **Stock Sync** | `OfficeGuyStock` class | `StockService` |
| **Subscriptions** | `OfficeGuySubscriptions` class | `SubscriptionService` |
| **Settings** | WordPress options | Laravel config file |
| **Hooks** | WordPress actions/filters | Laravel events |
| **Data Storage** | WordPress post meta | Eloquent models |
| **HTTP Requests** | `wp_remote_post()` | Guzzle HTTP client |
| **Logging** | Custom logging | Laravel Log facade |

## Mapping WooCommerce Hooks to Laravel Events

### Payment Hooks → Events

```php
// WooCommerce
add_action('woocommerce_payment_complete', 'my_callback');

// Laravel
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed;

Event::listen(PaymentProcessed::class, function ($event) {
    // $event->payment
    // $event->response
});
```

### Common Hook Mappings

| WooCommerce Hook | Laravel Event |
|-----------------|---------------|
| `woocommerce_payment_complete` | `PaymentProcessed` |
| `woocommerce_payment_failed` | `PaymentFailed` |
| `woocommerce_payment_token_added` | `TokenCreated` |
| Custom stock sync hook | `StockSynced` |

## Data Migration

### Migrating Payment Tokens

WooCommerce stores tokens in `wp_woocommerce_payment_tokens` table. Map to Laravel:

```php
// Migration script example
use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;

function migrateTokens() {
    $wpTokens = DB::table('wp_woocommerce_payment_tokens')
        ->where('gateway_id', 'officeguy')
        ->get();
    
    foreach ($wpTokens as $wpToken) {
        PaymentToken::create([
            'user_id' => $wpToken->user_id,
            'token' => $wpToken->token,
            'card_type' => 'card',
            'last_four' => $wpToken->last4,
            'expiry_month' => $wpToken->expiry_month,
            'expiry_year' => $wpToken->expiry_year,
            'is_default' => $wpToken->is_default,
            'created_at' => $wpToken->created,
        ]);
    }
}
```

### Migrating Payment Records

```php
// Map WooCommerce orders to Laravel payments
use NmDigitalHub\LaravelOfficeGuy\Models\Payment;

function migratePayments() {
    $orders = wc_get_orders([
        'payment_method' => 'officeguy',
        'limit' => -1,
    ]);
    
    foreach ($orders as $order) {
        Payment::create([
            'transaction_id' => get_post_meta($order->get_id(), '_transaction_id', true),
            'user_id' => $order->get_user_id(),
            'order_id' => $order->get_id(),
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => $order->is_paid() ? 'success' : 'pending',
            'document_number' => get_post_meta($order->get_id(), 'OfficeGuyDocumentID', true),
            'created_at' => $order->get_date_created(),
        ]);
    }
}
```

### Migrating Customer Data

```php
use NmDigitalHub\LaravelOfficeGuy\Models\Customer;

function migrateCustomers() {
    $wpCustomers = get_users(['role' => 'customer']);
    
    foreach ($wpCustomers as $wpCustomer) {
        Customer::create([
            'user_id' => $wpCustomer->ID,
            'name' => $wpCustomer->display_name,
            'email' => $wpCustomer->user_email,
            'phone' => get_user_meta($wpCustomer->ID, 'billing_phone', true),
            'address' => get_user_meta($wpCustomer->ID, 'billing_address_1', true),
            'city' => get_user_meta($wpCustomer->ID, 'billing_city', true),
            'zip_code' => get_user_meta($wpCustomer->ID, 'billing_postcode', true),
            'country' => get_user_meta($wpCustomer->ID, 'billing_country', true),
        ]);
    }
}
```

## Configuration Migration

### WooCommerce Settings → Laravel Config

```php
// WooCommerce settings access
$gateway = new WC_OfficeGuy();
$companyId = $gateway->settings['companyid'];

// Laravel config access
$companyId = config('officeguy.company_id');
```

### Settings Mapping

| WooCommerce Setting | Laravel Config |
|--------------------|----------------|
| `companyid` | `company_id` |
| `privatekey` | `api_private_key` |
| `publickey` | `api_public_key` |
| `environment` | `environment` |
| `merchantnumber` | `payment.merchant_number` |
| `testing` | `payment.testing_mode` |
| `authorizeonly` | `payment.authorize_only` |
| `draftdocument` | `payment.draft_document` |
| `emaildocument` | `payment.send_document_by_email` |
| `maxpayments` | `payment_limits.max_payments` |
| `support_tokens` | `tokens.support_tokens` |
| `stock_sync_freq` | `stock.sync_frequency` |

## Code Migration Examples

### Processing a Payment

```php
// WooCommerce
function processPayment($orderId) {
    $gateway = new WC_OfficeGuy();
    $order = wc_get_order($orderId);
    return OfficeGuyPayment::ProcessOrder($gateway, $order, false);
}

// Laravel
function processPayment($orderId) {
    $paymentService = app(PaymentService::class);
    $order = Order::find($orderId);
    
    return $paymentService->processPayment([
        'amount' => $order->total,
        'currency' => $order->currency,
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'customer' => [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
        ],
        'single_use_token' => request('payment_token'),
    ]);
}
```

### Creating a Token

```php
// WooCommerce
function createToken() {
    $gateway = new WC_OfficeGuy();
    return OfficeGuyTokens::ProcessToken($gateway);
}

// Laravel
function createToken() {
    $tokenService = app(TokenService::class);
    
    return $tokenService->createToken([
        'user_id' => auth()->id(),
        'single_use_token' => request('token'),
        'set_as_default' => true,
    ]);
}
```

### Stock Synchronization

```php
// WooCommerce
function syncStock() {
    OfficeGuyStock::UpdateStock();
}

// Laravel
function syncStock() {
    $stockService = app(StockService::class);
    return $stockService->updateStock(forceSync: true);
}
```

## API Endpoint Migration

### WooCommerce Ajax Actions → Laravel Routes

```php
// WooCommerce AJAX
add_action('wp_ajax_process_payment', 'process_payment_ajax');

// Laravel Route
Route::post('/api/officeguy/payments', [PaymentController::class, 'process']);
```

### Webhook URLs

```
WooCommerce: https://yoursite.com/?wc-api=WC_OfficeGuy
Laravel:     https://yoursite.com/api/officeguy/webhook
```

Update webhook URL in SUMIT dashboard.

## Authentication Migration

### WooCommerce User Management → Laravel Auth

```php
// WooCommerce
$userId = get_current_user_id();
$isLoggedIn = is_user_logged_in();

// Laravel
$userId = auth()->id();
$isLoggedIn = auth()->check();
```

## Subscription Migration

### WooCommerce Subscriptions → Laravel

```php
// WooCommerce
if (wcs_order_contains_subscription($orderId)) {
    // Handle subscription
}

// Laravel
$subscriptionService = app(SubscriptionService::class);

$result = $subscriptionService->createSubscription([
    'user_id' => auth()->id(),
    'amount' => $subscriptionAmount,
    'customer' => $customerData,
    'single_use_token' => $token,
]);
```

## Testing Migration

### Test Both Systems in Parallel

1. Keep WooCommerce plugin active
2. Install Laravel package
3. Test Laravel package with test orders
4. Compare results
5. Migrate production after successful testing

### Validation Checklist

- [ ] All payment processing works
- [ ] Token creation/storage works
- [ ] Subscriptions process correctly
- [ ] Stock sync functions properly
- [ ] Webhooks are received
- [ ] Events are triggered
- [ ] Data is properly stored
- [ ] Refunds work correctly
- [ ] Error handling is adequate
- [ ] Logging is comprehensive

## Rollback Plan

If issues arise:

1. **Keep WooCommerce Plugin**: Don't deactivate until fully migrated
2. **Database Backup**: Back up before migration
3. **Revert Config**: Easy to switch back to WooCommerce
4. **Webhook URLs**: Keep both active during transition

## Post-Migration

### Cleanup Steps

1. Verify all functionality in Laravel
2. Monitor logs for errors
3. Test edge cases
4. Update documentation
5. Train team on new system
6. Archive WooCommerce data
7. Deactivate WooCommerce plugin
8. Remove old webhook URLs

## Support During Migration

If you need assistance:

1. Review the [README.md](README.md)
2. Check [INSTALLATION.md](INSTALLATION.md)
3. Email: info@nm-digitalhub.com
4. GitHub Issues: Report migration issues

## Migration Timeline

Recommended timeline for migration:

1. **Week 1**: Setup and testing environment
2. **Week 2**: Data migration and validation
3. **Week 3**: Parallel testing
4. **Week 4**: Production migration
5. **Week 5**: Monitoring and optimization
6. **Week 6**: Cleanup and decommission WooCommerce

## Conclusion

The Laravel package provides a more robust, maintainable solution with better integration into Laravel's ecosystem. While migration requires effort, the long-term benefits of native Laravel integration, better testing, and improved maintainability make it worthwhile.
