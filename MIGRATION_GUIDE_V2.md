# Migration Guide: From Tightly-Coupled to Generic Architecture

This guide helps you migrate from the old tightly-coupled implementation to the new generic, event-driven architecture.

---

## Overview

### What Changed?

**Before (Tightly-Coupled):**
- Services wrote directly to Transaction model
- Events passed Eloquent models
- No way to use your own models
- Package forced database schema

**After (Generic):**
- Services fire events only
- Events use DTOs and primitive data
- Optional model listeners
- You control your database schema

---

## Migration Strategies

### Strategy 1: Keep Using Package Models (No Changes Needed)

**If you're happy with package models, do nothing!**

The old approach still works. Package models are enabled by default.

```php
// This still works
use Sumit\LaravelPayment\Services\PaymentService;

$result = app(PaymentService::class)->processPayment([
    'amount' => 100,
    'customer_name' => 'John Doe',
]);

$transaction = $result['transaction']; // Still works
```

---

### Strategy 2: Migrate to Generic Mode (Recommended)

**Best for:** Apps that want to use their own models

#### Step 1: Disable Package Models

In `.env`:

```env
SUMIT_TRANSACTION_MODEL=null
SUMIT_TOKEN_MODEL=null
SUMIT_CUSTOMER_MODEL=null
```

#### Step 2: Create Your Own Models

```php
// app/Models/Payment.php
class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
    ];
}
```

#### Step 3: Create Event Listeners

```php
// app/Listeners/StorePayment.php
use Sumit\LaravelPayment\Events\PaymentCompleted;

class StorePayment
{
    public function handle(PaymentCompleted $event): void
    {
        Payment::create([
            'order_id' => $event->metadata['order_id'],
            'gateway_transaction_id' => $event->transactionId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'status' => 'completed',
            'metadata' => $event->metadata,
        ]);
    }
}
```

#### Step 4: Update Event Listeners

**Old way:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    $transaction = $event->transaction; // Eloquent model
    $amount = $transaction->amount;
});
```

**New way:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    // Primitive data
    $transactionId = $event->transactionId;
    $amount = $event->amount;
    $metadata = $event->metadata;
});
```

#### Step 5: Update Controllers

**Old way:**
```php
use Sumit\LaravelPayment\Services\PaymentService;

$result = $service->processPayment($data);
$transaction = $result['transaction'];
```

**New way:**
```php
use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;

$paymentData = new PaymentData(
    amount: $data['amount'],
    customerName: $data['customer_name'],
    // ...
);

$response = $service->createPayment($paymentData);
// Events fired, listeners handle storage
```

---

### Strategy 3: Hybrid Approach

**Use package models + your own models**

Keep package models enabled but also create your own:

```php
Event::listen(PaymentCompleted::class, function($event) {
    // Package Transaction model is auto-created by package listener
    $packageTransaction = Transaction::where('transaction_id', $event->transactionId)->first();
    
    // Also create your own record
    Order::find($event->metadata['order_id'])->update([
        'sumit_transaction_id' => $packageTransaction->id,
        'payment_status' => 'paid',
    ]);
});
```

---

## Event Signature Changes

### PaymentCompleted

**Old:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    $event->transaction; // Eloquent model
});
```

**New:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    $event->transactionId;       // string
    $event->amount;              // float
    $event->currency;            // string
    $event->documentId;          // string|null
    $event->authorizationNumber; // string|null
    $event->customerId;          // string|null
    $event->metadata;            // array
});
```

### PaymentFailed

**Old:**
```php
Event::listen(PaymentFailed::class, function($event) {
    $event->transaction;  // Eloquent model
    $event->errorMessage; // string
});
```

**New:**
```php
Event::listen(PaymentFailed::class, function($event) {
    $event->transactionId; // string
    $event->errorMessage;  // string
    $event->amount;        // float|null
    $event->metadata;      // array
});
```

### TokenCreated

**Old:**
```php
Event::listen(TokenCreated::class, function($event) {
    $event->token; // PaymentToken model
});
```

**New:**
```php
Event::listen(TokenCreated::class, function($event) {
    $event->tokenData; // TokenData DTO
    $event->userId;    // mixed
});
```

---

## Service Changes

### Old PaymentService (Still Available)

```php
use Sumit\LaravelPayment\Services\PaymentService;

$service = app(PaymentService::class);
$result = $service->processPayment([
    'amount' => 100,
    'customer_name' => 'John Doe',
]);

// Returns array with 'transaction' model
```

### New GenericPaymentService

```php
use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;

$service = app(GenericPaymentService::class);
$response = $service->createPayment(new PaymentData(
    amount: 100,
    customerName: 'John Doe',
));

// Returns PaymentResponse DTO
```

---

## Database Migration

If you're switching from package models to your own:

### Step 1: Export Existing Data

```php
use Sumit\LaravelPayment\Models\Transaction;

// Export to CSV or backup
$transactions = Transaction::all();
$transactions->each(function($t) {
    MyPayment::create([
        'transaction_id' => $t->transaction_id,
        'amount' => $t->amount,
        // ... map fields
    ]);
});
```

### Step 2: Disable Package Models

```env
SUMIT_TRANSACTION_MODEL=null
```

### Step 3: Optionally Remove Migrations

```bash
# Remove package tables if not needed
php artisan migrate:rollback --path=vendor/nm-digital-hub/laravel-payment/database/migrations
```

---

## Common Migration Patterns

### Pattern 1: Order + Payment

**Before:**
```php
$result = PaymentService::processPayment($data);
$transaction = $result['transaction'];

Order::create([
    'sumit_transaction_id' => $transaction->id,
]);
```

**After:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    Order::find($event->metadata['order_id'])->update([
        'payment_gateway_id' => $event->transactionId,
        'status' => 'paid',
    ]);
});
```

### Pattern 2: Subscription

**Before:**
```php
$transaction = Transaction::create([...]);
Subscription::create([
    'transaction_id' => $transaction->id,
]);
```

**After:**
```php
Event::listen(PaymentCompleted::class, function($event) {
    Subscription::find($event->metadata['subscription_id'])->update([
        'status' => 'active',
    ]);
});
```

---

## Testing During Migration

### Run Both Approaches in Parallel

```php
// Keep old code working
Event::listen(PaymentCompleted::class, OldListener::class);

// Test new approach
Event::listen(PaymentCompleted::class, NewListener::class);

// Both listeners run, compare results
```

### Gradual Rollout

1. Enable both old and new listeners
2. Test new listeners in staging
3. Compare data between old and new
4. Once confident, disable old listeners
5. Remove old code

---

## Rollback Plan

If something goes wrong:

### Quick Rollback

```env
# Re-enable package models
SUMIT_TRANSACTION_MODEL=Sumit\LaravelPayment\Models\Transaction
SUMIT_TOKEN_MODEL=Sumit\LaravelPayment\Models\PaymentToken
```

### Full Rollback

1. Re-enable package models in `.env`
2. Revert event listeners to old code
3. Use old PaymentService instead of GenericPaymentService

---

## Benefits of Migration

After migrating to Generic Mode:

✅ **Full Control** - Your database, your schema  
✅ **Future-Proof** - Package updates won't break your code  
✅ **Flexibility** - Use any model structure  
✅ **Testability** - Easier to mock events than models  
✅ **Multiple Gateways** - Same event listeners work for all gateways  

---

## Migration Checklist

- [ ] Backup existing data
- [ ] Create your own models (if needed)
- [ ] Create event listeners
- [ ] Register listeners in EventServiceProvider
- [ ] Update controllers to use GenericPaymentService
- [ ] Test payment flow end-to-end
- [ ] Test webhook handling
- [ ] Disable package models in `.env`
- [ ] Remove old code
- [ ] Deploy to production

---

## Need Help?

- 📖 [GENERIC_ARCHITECTURE.md](GENERIC_ARCHITECTURE.md) - Architecture guide
- 📖 [INTEGRATION_EXAMPLES.md](INTEGRATION_EXAMPLES.md) - Real-world examples
- 📖 [QUICK_START.md](QUICK_START.md) - Quick start guide
- 🐛 GitHub Issues - Report problems

---

## Summary

**The package is now like a library, not a framework:**

- It doesn't force opinions on your code
- It doesn't know about your models
- It fires events, you handle the rest
- You can still use package models if you want
- Backward compatibility maintained

**Choose the approach that works best for your project!**
