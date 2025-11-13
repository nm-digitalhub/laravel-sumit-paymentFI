# WooCommerce to Laravel Migration Guide

This document provides a mapping between the original WooCommerce plugin functionality and the new Laravel package implementation.

## Architecture Overview

### WooCommerce Plugin
- WordPress plugin architecture
- Procedural PHP with class-based components
- WordPress hooks and filters
- WooCommerce order system
- WordPress database tables
- Global functions and constants

### Laravel Package
- Laravel service provider architecture
- Object-oriented design with dependency injection
- Laravel events and listeners
- Generic transaction system
- Laravel Eloquent ORM and migrations
- Service classes and facades

## Hook to Event Mapping

### Custom Hooks Available

#### 1. Maximum Installments Hook

**WooCommerce (Original):**
```php
function CustomInstallmentsLogic($MaximumPayments, $OrderValue) {
    return 5;
}
add_filter('sumit_maximum_installments', 'CustomInstallmentsLogic', 10, 2);
```

**Laravel (New):**
```php
// In your AppServiceProvider or custom service
use Sumit\LaravelPayment\Services\PaymentService;

// Option 1: Override in configuration
config(['sumit-payment.maximum_payments' => 5]);

// Option 2: Extend PaymentService
class CustomPaymentService extends PaymentService
{
    protected function getMaximumPayments(float $amount): int
    {
        // Your custom logic
        return 5;
    }
}

// Register in service provider
$this->app->singleton(PaymentService::class, function ($app) {
    return new CustomPaymentService(
        $app->make(ApiService::class),
        $app->make(TokenService::class)
    );
});
```

#### 2. Custom Customer Fields Hook

**WooCommerce (Original):**
```php
function CustomCustomerFields($Customer, $Order) {
    $Customer['Billing last name'] = $Order->get_billing_last_name();
    return $Customer;
}
add_filter('sumit_customer_fields', 'CustomCustomerFields', 10, 2);
```

**Laravel (New):**
```php
// Option 1: Extend PaymentService
class CustomPaymentService extends PaymentService
{
    protected function buildCustomer(array $paymentData): array
    {
        $customer = parent::buildCustomer($paymentData);
        
        // Add your custom fields
        $customer['Billing last name'] = $paymentData['customer_last_name'] ?? '';
        
        return $customer;
    }
}

// Option 2: Use an event listener
use Sumit\LaravelPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    // Update customer data after payment
    $transaction = $event->transaction;
    // Your custom logic
});
```

#### 3. Custom Item Fields Hook

**WooCommerce (Original):**
```php
function CustomItemFields($Item, $Product, $UnitPrice, $OrderItem, $Order) {
    $Item['Name'] = $Item['Name'] . ' - ' . $Product->get_sku();
    
    if ($UnitPrice == 0)
        return null;
    
    return $Item;
}
add_filter('sumit_item_fields', 'CustomItemFields', 10, 5);
```

**Laravel (New):**
```php
// Extend PaymentService
class CustomPaymentService extends PaymentService
{
    protected function buildItems(array $paymentData): array
    {
        $items = parent::buildItems($paymentData);
        
        // Customize items
        foreach ($items as $key => $item) {
            // Add SKU to name
            if (isset($item['SKU'])) {
                $items[$key]['Name'] = $item['Name'] . ' - ' . $item['SKU'];
            }
            
            // Remove zero-priced items
            if ($item['Price'] == 0) {
                unset($items[$key]);
            }
        }
        
        return array_values($items);
    }
}
```

## Events System

### Available Events

#### PaymentCompleted Event

Dispatched when a payment is successfully processed.

```php
use Sumit\LaravelPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    
    // Access transaction data
    $amount = $transaction->amount;
    $userId = $transaction->user_id;
    $orderId = $transaction->order_id;
    
    // Your custom logic
    // - Send confirmation email
    // - Update order status
    // - Trigger fulfillment
    // - Award loyalty points
});
```

#### PaymentFailed Event

Dispatched when a payment fails.

```php
use Sumit\LaravelPayment\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;
    $error = $event->errorMessage;
    
    // Your custom logic
    // - Log error
    // - Notify admin
    // - Send failure email to customer
    // - Retry logic
});
```

#### TokenCreated Event

Dispatched when a new payment token is created.

```php
use Sumit\LaravelPayment\Events\TokenCreated;

Event::listen(TokenCreated::class, function (TokenCreated $event) {
    $token = $event->token;
    
    // Your custom logic
    // - Send notification to user
    // - Log token creation
    // - Update user preferences
});
```

## Component Mapping

### File Mapping

| WooCommerce File | Laravel Equivalent | Description |
|-----------------|-------------------|-------------|
| `OfficeGuyAPI.php` | `Services/ApiService.php` | API communication |
| `OfficeGuyPayment.php` | `Services/PaymentService.php` | Payment processing logic |
| `OfficeGuyTokens.php` | `Services/TokenService.php` | Token management |
| `OfficeGuySettings.php` | `config/sumit-payment.php` | Configuration settings |
| `OfficeGuySubscriptions.php` | Integrated into `PaymentService` | Subscription handling |
| `OfficeGuyStock.php` | Not included (app-specific) | Stock synchronization |
| `OfficeGuyPluginSetup.php` | `SumitPaymentServiceProvider.php` | Package setup |
| `officeguy_woocommerce_gateway.php` | `Controllers/PaymentController.php` | Payment endpoint |

### Database Mapping

| WooCommerce Storage | Laravel Model | Migration |
|-------------------|--------------|-----------|
| `wp_postmeta` (tokens) | `PaymentToken` | `create_sumit_payment_tokens_table` |
| `wp_postmeta` (orders) | `Transaction` | `create_sumit_transactions_table` |
| `wp_users` (customers) | `Customer` | `create_sumit_customers_table` |

### Action/Filter Mapping

| WooCommerce Hook | Laravel Approach |
|-----------------|------------------|
| `add_action()` | Event listeners |
| `add_filter()` | Service extension or config |
| `do_action()` | `Event::dispatch()` |
| `apply_filters()` | Method overriding |

## Configuration Mapping

| WooCommerce Setting | Laravel Config |
|--------------------|----------------|
| Company ID | `sumit-payment.company_id` |
| API Key | `sumit-payment.api_key` |
| API Public Key | `sumit-payment.api_public_key` |
| Environment | `sumit-payment.environment` |
| Testing Mode | `sumit-payment.testing_mode` |
| PCI Mode | `sumit-payment.pci_mode` |
| Merchant Number | `sumit-payment.merchant_number` |
| Draft Document | `sumit-payment.draft_document` |
| Email Document | `sumit-payment.email_document` |
| Maximum Payments | `sumit-payment.maximum_payments` |

## Integration Examples

### E-commerce Integration

```php
// In your order controller
use Sumit\LaravelPayment\Facades\SumitPayment;

public function checkout(Request $request)
{
    $order = Order::create([...]);
    
    $result = SumitPayment::processPayment([
        'amount' => $order->total,
        'order_id' => $order->id,
        'customer_name' => $order->customer_name,
        'customer_email' => $order->customer_email,
        'card_number' => $request->card_number,
        'expiry_month' => $request->expiry_month,
        'expiry_year' => $request->expiry_year,
        'cvv' => $request->cvv,
        'items' => $order->items->map(fn($item) => [
            'Name' => $item->name,
            'Price' => $item->price,
            'Quantity' => $item->quantity,
        ])->toArray(),
    ]);
    
    if ($result['success']) {
        $order->update(['status' => 'paid']);
        return redirect()->route('order.success');
    }
    
    return back()->withErrors(['payment' => $result['message']]);
}
```

### Subscription Integration

```php
// Listen for successful payments to create subscriptions
use Sumit\LaravelPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    if ($event->transaction->is_subscription) {
        Subscription::create([
            'user_id' => $event->transaction->user_id,
            'transaction_id' => $event->transaction->id,
            'amount' => $event->transaction->amount,
            'status' => 'active',
        ]);
    }
});
```

### Token Management

```php
// Save card during checkout
public function saveCard(Request $request)
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
```

## Migration Checklist

- [x] Convert plugin structure to Laravel package
- [x] Create Service Provider
- [x] Create database migrations
- [x] Implement Models with Eloquent
- [x] Build Service classes (API, Payment, Token)
- [x] Create Controllers
- [x] Implement Events system
- [x] Configure routes
- [x] Create Facade
- [x] Write comprehensive documentation
- [ ] Add unit tests (optional, based on existing test infrastructure)
- [ ] Add integration tests (optional, based on existing test infrastructure)
- [ ] Performance optimization
- [ ] Security audit

## Notes for Developers

1. **Custom Logic**: Extend service classes instead of using hooks
2. **Events**: Use Laravel events for lifecycle hooks
3. **Configuration**: Use config files and environment variables
4. **Database**: Use Eloquent models and migrations
5. **Testing**: Write unit and feature tests using PHPUnit
6. **Logging**: Use Laravel's logging system
7. **Validation**: Use Laravel's validation system
8. **Authentication**: Integrate with Laravel's auth system

## Support

For migration assistance or questions, contact support@sumit.co.il
