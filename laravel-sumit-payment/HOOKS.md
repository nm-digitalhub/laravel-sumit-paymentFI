# Custom Hooks System

The Laravel SUMIT Payment package provides a hooks system similar to WooCommerce's filters and actions, implemented using Laravel's event system.

## Overview

The hooks system allows you to customize payment processing behavior without modifying the package code. Each hook is implemented as a Laravel event that you can listen to and modify.

## Available Hooks

### 1. Maximum Installments Hook

Customize the maximum number of installments allowed for a payment.

**Event:** `NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments`

**Properties:**
- `maxPayments` (int) - Current maximum installments
- `orderValue` (float) - Total order amount

**Example:**

```php
<?php

namespace App\Listeners;

use NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments;

class CustomInstallmentsLogic
{
    public function handle(MaximumInstallments $event)
    {
        // Premium customers get more installments
        if (auth()->user()?->is_premium) {
            $event->setMaxPayments(24);
            return;
        }

        // High value orders get more installments
        if ($event->orderValue > 5000) {
            $event->setMaxPayments(18);
            return;
        }

        // Default: 5 installments for all orders
        $event->setMaxPayments(5);
    }
}
```

Register in `EventServiceProvider`:

```php
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments;
use App\Listeners\CustomInstallmentsLogic;

protected $listen = [
    MaximumInstallments::class => [
        CustomInstallmentsLogic::class,
    ],
];
```

### 2. Customer Fields Hook

Customize customer data sent to SUMIT API.

**Event:** `NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields`

**Properties:**
- `customer` (array) - Customer data array
- `orderData` (array) - Original order data

**Methods:**
- `setField(string $key, $value)` - Set a customer field
- `removeField(string $key)` - Remove a customer field

**Example:**

```php
<?php

namespace App\Listeners;

use NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields;

class CustomCustomerFields
{
    public function handle(CustomerFields $event)
    {
        // Add billing last name
        if (isset($event->orderData['billing_last_name'])) {
            $event->setField('BillingLastName', $event->orderData['billing_last_name']);
        }

        // Add custom company registration number
        if (isset($event->orderData['company_registration'])) {
            $event->setField('CompanyRegistration', $event->orderData['company_registration']);
        }

        // Add customer notes
        $event->setField('Notes', 'Customer from Laravel application');
    }
}
```

### 3. Item Fields Hook

Customize item data before sending to SUMIT API.

**Event:** `NmDigitalHub\LaravelSumitPayment\Events\Hooks\ItemFields`

**Properties:**
- `item` (array) - Item data array
- `product` (array) - Product information
- `unitPrice` (float) - Item unit price
- `orderItemData` (array) - Order item data
- `orderData` (array) - Full order data

**Methods:**
- `setField(string $key, $value)` - Set an item field
- `removeItem()` - Mark item for removal
- `shouldRemove()` - Check if item should be removed

**Example:**

```php
<?php

namespace App\Listeners;

use NmDigitalHub\LaravelSumitPayment\Events\Hooks\ItemFields;

class CustomItemFields
{
    public function handle(ItemFields $event)
    {
        // Add SKU to item name
        if (!empty($event->product['sku'])) {
            $currentName = $event->item['Name'] ?? '';
            $event->setField('Name', $currentName . ' - ' . $event->product['sku']);
        }

        // Remove zero-priced items
        if ($event->unitPrice == 0) {
            $event->removeItem();
            return;
        }

        // Add category information
        if (!empty($event->product['category'])) {
            $event->setField('Category', $event->product['category']);
        }

        // Add custom description
        if (!empty($event->product['description'])) {
            $event->setField('Description', $event->product['description']);
        }
    }
}
```

## Hook Processing Flow

The hooks are processed in the following order during payment:

1. **Item Processing**
   - For each item in the order
   - `ItemFields` event is fired
   - Item can be modified or removed

2. **Customer Processing**
   - Customer data is built from order
   - `CustomerFields` event is fired
   - Customer fields can be added or modified

3. **Payment Calculation**
   - Maximum installments are calculated
   - `MaximumInstallments` event is fired
   - Installment count can be adjusted

4. **Payment Processing**
   - Modified data is sent to SUMIT API
   - Standard events (`PaymentProcessing`, `PaymentCompleted`) are fired

## Quick Setup Guide

### Step 1: Create Listener

```bash
php artisan make:listener CustomPaymentHooks
```

### Step 2: Implement Handler

```php
<?php

namespace App\Listeners;

use NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments;
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields;
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\ItemFields;

class CustomPaymentHooks
{
    public function handleInstallments(MaximumInstallments $event)
    {
        // Your custom installments logic
    }

    public function handleCustomer(CustomerFields $event)
    {
        // Your custom customer fields logic
    }

    public function handleItem(ItemFields $event)
    {
        // Your custom item fields logic
    }
}
```

### Step 3: Register in EventServiceProvider

```php
protected $listen = [
    \NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments::class => [
        \App\Listeners\CustomPaymentHooks::class . '@handleInstallments',
    ],
    \NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields::class => [
        \App\Listeners\CustomPaymentHooks::class . '@handleCustomer',
    ],
    \NmDigitalHub\LaravelSumitPayment\Events\Hooks\ItemFields::class => [
        \App\Listeners\CustomPaymentHooks::class . '@handleItem',
    ],
];
```

## Advanced: Multiple Listeners

You can register multiple listeners for the same hook:

```php
protected $listen = [
    MaximumInstallments::class => [
        PremiumCustomerInstallments::class,
        SeasonalInstallments::class,
        DefaultInstallments::class,
    ],
];
```

Listeners are executed in order. Each can modify the data, and changes are passed to the next listener.

## Testing Hooks

You can test your hooks using Laravel's event faking:

```php
use Illuminate\Support\Facades\Event;
use NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments;

public function test_custom_installments_logic()
{
    Event::fake([MaximumInstallments::class]);

    // Your test code that triggers payment

    Event::assertDispatched(MaximumInstallments::class, function ($event) {
        return $event->maxPayments === 24;
    });
}
```

## Migration from WooCommerce

If you're migrating from the WooCommerce plugin, here's the mapping:

| WooCommerce Filter | Laravel Event |
|-------------------|---------------|
| `sumit_maximum_installments` | `MaximumInstallments` |
| `sumit_customer_fields` | `CustomerFields` |
| `sumit_item_fields` | `ItemFields` |

Example WooCommerce code:

```php
// WooCommerce
add_filter('sumit_maximum_installments', function($max, $value) {
    return 5;
});
```

Becomes in Laravel:

```php
// Laravel
Event::listen(MaximumInstallments::class, function($event) {
    $event->setMaxPayments(5);
});
```

## Best Practices

1. **Keep listeners focused** - Each listener should handle one specific concern
2. **Validate data** - Always validate before modifying event data
3. **Handle edge cases** - Check for null/empty values
4. **Log changes** - Log modifications for debugging
5. **Don't break the chain** - Ensure your modifications don't break payment processing
6. **Test thoroughly** - Test with various scenarios and edge cases

## Troubleshooting

### Hook not firing

- Ensure listener is registered in `EventServiceProvider`
- Run `php artisan event:cache` to clear event cache
- Check if the payment flow reaches the hook point

### Changes not applied

- Verify listener is using correct method (e.g., `setField()`)
- Check listener execution order
- Enable logging to see hook execution

### Errors during payment

- Validate hook modifications don't break required fields
- Check SUMIT API requirements for modified fields
- Review error logs for specific issues
