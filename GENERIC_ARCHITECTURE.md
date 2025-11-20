# Generic Architecture Guide

## 🎯 Core Principle: Zero Assumptions

This package follows a **zero-assumptions architecture**, meaning it doesn't force you to:
- Use specific model names (User, Order, Invoice, etc.)
- Follow a particular database schema
- Implement specific business logic
- Use the package's models at all

**The package is like a library, not a framework.**

---

## 📦 How It Works

### 1. DTOs (Data Transfer Objects)

The package uses DTOs for all data transfer, making it completely model-agnostic:

```php
use Sumit\LaravelPayment\DTO\PaymentData;
use Sumit\LaravelPayment\DTO\PaymentResponse;

// Create payment data
$paymentData = new PaymentData(
    amount: 100.00,
    currency: 'ILS',
    customerName: 'John Doe',
    customerEmail: 'john@example.com',
    metadata: ['order_id' => 123] // Your custom data
);
```

**Available DTOs:**
- `PaymentData` - Payment request data
- `PaymentResponse` - Payment response data
- `TokenData` - Token data
- `RefundData` - Refund request data

### 2. Events (The Communication Bridge)

All operations fire events with **primitive data or DTOs** (no models):

```php
// PaymentCompleted event
Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    // Access primitive data
    $event->transactionId;  // string
    $event->amount;         // float
    $event->currency;       // string
    $event->metadata;       // array - your custom data
});
```

**Available Events:**
- `PaymentCreated(PaymentResponse $response, PaymentData $request)`
- `PaymentCompleted(string $transactionId, float $amount, ...)`
- `PaymentFailed(string $transactionId, string $errorMessage, ...)`
- `PaymentRefunded(string $transactionId, float $refundAmount, ...)`
- `TokenCreated(TokenData $tokenData, mixed $userId)`

### 3. Contracts/Interfaces

You can implement your own payment logic:

```php
use Sumit\LaravelPayment\Contracts\PaymentGatewayInterface;

class MyCustomPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(PaymentData $data): PaymentResponse
    {
        // Add your custom logic
        $this->validateBusinessRules($data);
        
        // Call SUMIT or wrap it
        return $this->sumitService->createPayment($data);
    }
}

// Bind in your AppServiceProvider
$this->app->bind(PaymentGatewayInterface::class, MyCustomPaymentGateway::class);
```

---

## 🎨 Three Ways to Use This Package

### Level 1: Events Only (Zero Package Models) ✅ RECOMMENDED

**Completely ignore the package models and use your own:**

```php
// Your app structure
app/
├── Models/
│   ├── Order.php           // Your order model
│   └── Payment.php         // Your payment model
└── Listeners/
    └── StorePayment.php

// Your listener
class StorePayment
{
    public function handle(PaymentCompleted $event): void
    {
        // Store in YOUR database, YOUR way
        Payment::create([
            'order_id' => $event->metadata['order_id'],
            'gateway_transaction_id' => $event->transactionId,
            'amount' => $event->amount,
            'status' => 'completed',
        ]);
        
        // Update YOUR order
        Order::find($event->metadata['order_id'])->update([
            'payment_status' => 'paid',
        ]);
    }
}

// Register in EventServiceProvider
protected $listen = [
    PaymentCompleted::class => [
        StorePayment::class,
    ],
];
```

**Disable package models in `.env`:**
```env
SUMIT_TRANSACTION_MODEL=null
SUMIT_TOKEN_MODEL=null
SUMIT_CUSTOMER_MODEL=null
```

**Benefits:**
- ✅ 100% control over your data
- ✅ No imposed database schema
- ✅ Package updates won't affect your code
- ✅ Perfect for existing applications

---

### Level 2: Use Package Models (Optional)

**Let the package handle storage for you:**

```php
// .env (default - models are enabled)
SUMIT_TRANSACTION_MODEL=Sumit\LaravelPayment\Models\Transaction
SUMIT_TOKEN_MODEL=Sumit\LaravelPayment\Models\PaymentToken

// The package automatically stores data when events fire
// You can still listen to events for additional logic
```

**Use package models in your code:**
```php
use Sumit\LaravelPayment\Models\Transaction;

// Query transactions
$payments = Transaction::where('user_id', auth()->id())
    ->completed()
    ->get();
```

**Benefits:**
- ✅ Quick start
- ✅ Pre-built models and migrations
- ✅ Can still use events for custom logic

---

### Level 3: Hybrid Approach

**Use package models + your own models:**

```php
class LinkPaymentToOrder
{
    public function handle(PaymentCompleted $event): void
    {
        // Package stores in Transaction model automatically
        $transaction = Transaction::where('transaction_id', $event->transactionId)->first();
        
        // You link it to your own models
        Order::find($event->metadata['order_id'])->update([
            'sumit_transaction_id' => $transaction->id,
            'payment_status' => 'paid',
        ]);
    }
}
```

---

## 📖 Real-World Examples

### Example 1: E-commerce Store

```php
// Your listener
class ProcessOrderPayment
{
    public function handle(PaymentCompleted $event): void
    {
        $orderId = $event->metadata['order_id'];
        
        // Update order
        $order = Order::find($orderId);
        $order->update(['status' => 'paid']);
        
        // Create invoice
        Invoice::create([
            'order_id' => $orderId,
            'amount' => $event->amount,
            'paid_at' => now(),
        ]);
        
        // Send email
        Mail::to($order->customer)->send(new OrderPaid($order));
        
        // Trigger fulfillment
        FulfillmentJob::dispatch($order);
    }
}
```

### Example 2: SaaS Subscription

```php
class ActivateSubscription
{
    public function handle(PaymentCompleted $event): void
    {
        $subscriptionId = $event->metadata['subscription_id'];
        
        Subscription::find($subscriptionId)->update([
            'status' => 'active',
            'activated_at' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);
        
        User::find($event->metadata['user_id'])->grantAccess();
    }
}
```

### Example 3: Donation Platform

```php
class RecordDonation
{
    public function handle(PaymentCompleted $event): void
    {
        Donation::create([
            'campaign_id' => $event->metadata['campaign_id'],
            'donor_email' => $event->metadata['email'],
            'amount' => $event->amount,
            'receipt_id' => $event->documentId,
        ]);
        
        // Send tax receipt
        if ($event->amount >= 100) {
            TaxReceipt::generate($event->transactionId);
        }
    }
}
```

---

## 🔧 Configuration

### API Configuration

```php
// .env
SUMIT_COMPANY_ID=your-company-id
SUMIT_API_KEY=your-api-key
SUMIT_MERCHANT_NUMBER=your-merchant-number
```

### Models Configuration (Optional)

```php
// config/sumit-payment.php
'models' => [
    'transaction' => null,  // Disable transaction model
    'token' => \Sumit\LaravelPayment\Models\PaymentToken::class,  // Enable token model
    'customer' => null,     // Disable customer model
],
```

### Callbacks (Quick Customization)

```php
// config/sumit-payment.php
'callbacks' => [
    'before_payment' => function(\Sumit\LaravelPayment\DTO\PaymentData $data) {
        // Validate before payment
        if ($data->amount > 10000) {
            throw new \Exception('Amount too high');
        }
    },
    
    'after_payment_success' => function(\Sumit\LaravelPayment\DTO\PaymentResponse $response) {
        // Log successful payment
        Log::info('Payment successful', ['id' => $response->transactionId]);
    },
],
```

---

## 🎯 Package vs App Responsibilities

### ✅ Package Handles:
- SUMIT API communication
- Request/Response DTOs
- Event firing
- Webhook processing
- Token encryption
- Optional basic models

### ❌ Package Does NOT Handle:
- Your database schema
- Your business logic
- Order/Invoice/User management
- Email notifications (you handle via events)
- Frontend UI

### ✅ Your App Handles:
- Listen to events
- Store data in your schema
- Implement business rules
- Send notifications
- UI/UX

---

## 📚 Migration from Old Architecture

If you were using the package before:

**Old way (tightly coupled):**
```php
// Service wrote directly to database
$result = PaymentService::processPayment($data);
$transaction = $result['transaction']; // Eloquent model
```

**New way (decoupled):**
```php
// Service fires events, you decide what to do
Event::listen(PaymentCompleted::class, function($event) {
    // Store in your own way
});
```

**Backward Compatibility:**
The old models still exist and work if you keep them enabled in config.

---

## 🔒 Security

The package:
- Never stores credit card data
- Uses PCI-compliant tokenization
- Encrypts sensitive data
- Verifies webhook signatures
- Sanitizes logs

---

## 💡 Best Practices

1. **Always use metadata** to pass custom data:
   ```php
   new PaymentData(
       amount: 100,
       metadata: [
           'order_id' => 123,
           'user_id' => 456,
           'custom_field' => 'value'
       ]
   )
   ```

2. **Listen to events, don't poll**:
   ```php
   // ✅ Good
   Event::listen(PaymentCompleted::class, ...);
   
   // ❌ Bad
   while (!$payment->isCompleted()) { sleep(1); }
   ```

3. **Use webhooks for async updates**:
   ```php
   // Events fire on webhooks too
   // So your listeners work for both direct and webhook flows
   ```

4. **Disable unused models**:
   ```env
   # Only enable what you use
   SUMIT_TRANSACTION_MODEL=null
   ```

---

## 🚀 Quick Start for New Projects

1. **Install:**
   ```bash
   composer require nm-digital-hub/laravel-payment
   ```

2. **Configure API:**
   ```env
   SUMIT_COMPANY_ID=xxx
   SUMIT_API_KEY=xxx
   ```

3. **Disable models (use your own):**
   ```env
   SUMIT_TRANSACTION_MODEL=null
   SUMIT_TOKEN_MODEL=null
   ```

4. **Listen to events:**
   ```php
   Event::listen(PaymentCompleted::class, YourListener::class);
   ```

5. **Process payments:**
   ```php
   use Sumit\LaravelPayment\DTO\PaymentData;
   use Sumit\LaravelPayment\Services\PaymentService;
   
   $payment = app(PaymentService::class);
   $response = $payment->createPayment(new PaymentData(...));
   ```

---

## ❓ FAQ

**Q: Do I need to use the package models?**
A: No! Disable them in config and use events to store data your way.

**Q: Can I use my own User model?**
A: Yes! The package doesn't care about your User model. Use `$userId` parameter.

**Q: What if I have Orders, not Transactions?**
A: Perfect! Listen to events and save to your Order model.

**Q: Can I validate before payment?**
A: Yes! Use callbacks or implement `PaymentGatewayInterface`.

**Q: How do I handle webhooks?**
A: Same events fire for webhooks. Your listeners handle both cases.

---

## 📞 Support

For questions, please create an issue or consult the main [README.md](README.md).
