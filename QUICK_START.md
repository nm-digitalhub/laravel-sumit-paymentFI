# Quick Start Guide

This guide will help you get started with the SUMIT Payment Package in just a few minutes.

---

## Installation

```bash
composer require nm-digital-hub/laravel-payment
```

---

## Choose Your Approach

### Option A: Generic Mode (Recommended) ✨

**Best for:** New projects, existing applications with their own models

#### Step 1: Configure API

Create `.env` entries:

```env
SUMIT_COMPANY_ID=your-company-id
SUMIT_API_KEY=your-api-key
SUMIT_API_PUBLIC_KEY=your-public-key
SUMIT_MERCHANT_NUMBER=your-merchant-number

# Disable package models
SUMIT_TRANSACTION_MODEL=null
SUMIT_TOKEN_MODEL=null
SUMIT_CUSTOMER_MODEL=null
```

#### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

#### Step 3: Create Event Listener

```bash
php artisan make:listener StorePayment
```

```php
<?php

namespace App\Listeners;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Models\Payment;

class StorePayment
{
    public function handle(PaymentCompleted $event): void
    {
        Payment::create([
            'transaction_id' => $event->transactionId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'status' => 'completed',
            'metadata' => $event->metadata,
        ]);
    }
}
```

#### Step 4: Register Listener

In `app/Providers/EventServiceProvider.php`:

```php
use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Listeners\StorePayment;

protected $listen = [
    PaymentCompleted::class => [
        StorePayment::class,
    ],
];
```

#### Step 5: Process Payment

```php
use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;

class PaymentController extends Controller
{
    public function process(Request $request, GenericPaymentService $payment)
    {
        $data = new PaymentData(
            amount: $request->amount,
            currency: 'ILS',
            customerName: $request->customer_name,
            customerEmail: $request->customer_email,
            cardNumber: $request->card_number,
            expiryMonth: $request->expiry_month,
            expiryYear: $request->expiry_year,
            cvv: $request->cvv,
            metadata: [
                'order_id' => $request->order_id,
                'user_id' => auth()->id(),
            ]
        );

        $response = $payment->createPayment($data);

        if ($response->isSuccessful()) {
            return redirect()->route('payment.success');
        }

        return back()->withErrors(['payment' => $response->errorMessage]);
    }
}
```

**Done!** The package fires events, your listener stores the data.

---

### Option B: Traditional Mode (Quick Start)

**Best for:** Quick prototyping, simple applications

#### Step 1: Configure API

Create `.env` entries:

```env
SUMIT_COMPANY_ID=your-company-id
SUMIT_API_KEY=your-api-key
SUMIT_API_PUBLIC_KEY=your-public-key
SUMIT_MERCHANT_NUMBER=your-merchant-number
```

#### Step 2: Publish & Migrate

```bash
php artisan vendor:publish --tag=sumit-payment-config
php artisan vendor:publish --tag=sumit-payment-migrations
php artisan migrate
```

#### Step 3: Process Payment

```php
use Sumit\LaravelPayment\Services\PaymentService;

class PaymentController extends Controller
{
    public function process(Request $request, PaymentService $payment)
    {
        $result = $payment->processPayment([
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'card_number' => $request->card_number,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'cvv' => $request->cvv,
        ]);

        if ($result['success']) {
            $transaction = $result['transaction'];
            return redirect()->route('payment.success', $transaction);
        }

        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

#### Step 4: View Transactions

```php
use Sumit\LaravelPayment\Models\Transaction;

class DashboardController extends Controller
{
    public function transactions()
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->completed()
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.transactions', compact('transactions'));
    }
}
```

**Done!** Package handles everything automatically.

---

## Testing Your Integration

### Test Card Numbers

For testing, use these test card numbers:

```
Success: 4580000000000000
Declined: 4580000000000001
```

### Enable Test Mode

In `.env`:

```env
SUMIT_TESTING_MODE=true
```

---

## Common Scenarios

### Scenario 1: E-commerce Checkout

```php
use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;

public function checkout(Request $request, GenericPaymentService $payment)
{
    $order = Order::create([
        'user_id' => auth()->id(),
        'total' => $request->total,
        'status' => 'pending',
    ]);

    $data = new PaymentData(
        amount: $order->total,
        currency: 'ILS',
        customerName: auth()->user()->name,
        customerEmail: auth()->user()->email,
        description: "Order #{$order->id}",
        metadata: ['order_id' => $order->id]
    );

    $response = $payment->createPayment($data);

    return response()->json([
        'success' => $response->isSuccessful(),
        'payment_url' => $response->paymentUrl,
    ]);
}
```

### Scenario 2: Subscription Payment

```php
$data = new PaymentData(
    amount: 99.00,
    currency: 'ILS',
    customerName: $user->name,
    customerEmail: $user->email,
    isSubscription: true,
    metadata: [
        'subscription_id' => $subscription->id,
        'plan_id' => $plan->id,
    ]
);

$response = $payment->createPayment($data);
```

### Scenario 3: Donation

```php
$data = new PaymentData(
    amount: $request->amount,
    currency: 'ILS',
    customerName: $request->donor_name,
    customerEmail: $request->donor_email,
    isDonation: true,
    metadata: [
        'campaign_id' => $campaign->id,
        'is_anonymous' => $request->is_anonymous,
    ]
);

$response = $payment->createPayment($data);
```

---

## Listening to Events

### Handle Payment Success

```php
Event::listen(PaymentCompleted::class, function($event) {
    // Send receipt
    Mail::to($event->metadata['email'])->send(new Receipt($event));
    
    // Update order
    Order::find($event->metadata['order_id'])->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);
    
    // Trigger fulfillment
    ProcessOrder::dispatch($event->metadata['order_id']);
});
```

### Handle Payment Failure

```php
Event::listen(PaymentFailed::class, function($event) {
    // Log error
    Log::error('Payment failed', [
        'transaction_id' => $event->transactionId,
        'error' => $event->errorMessage,
    ]);
    
    // Notify admin
    Admin::notify(new PaymentFailedNotification($event));
});
```

---

## Webhooks

### Setup Webhook URL

In SUMIT dashboard, configure webhook URL:

```
https://your-domain.com/sumit/webhook
```

### Handle Webhooks

Events automatically fire for webhooks too! Your same listeners will handle them:

```php
// This listener handles both direct payments AND webhooks
Event::listen(PaymentCompleted::class, function($event) {
    // Works for both!
});
```

---

## Security Settings

### Production Checklist

```env
# Production settings
SUMIT_TESTING_MODE=false
SUMIT_ENVIRONMENT=www
SUMIT_VERIFY_SIGNATURE=true
SUMIT_LOGGING_ENABLED=true
```

### Signature Verification

The package automatically verifies webhook signatures using your API key.

---

## Next Steps

- 📖 Read [GENERIC_ARCHITECTURE.md](GENERIC_ARCHITECTURE.md) for architecture details
- 📖 Read [INTEGRATION_EXAMPLES.md](INTEGRATION_EXAMPLES.md) for real-world examples
- 📖 Read [README.md](README.md) for API documentation

---

## Troubleshooting

### "No response from payment gateway"

Check your API credentials in `.env`:
- SUMIT_COMPANY_ID
- SUMIT_API_KEY
- SUMIT_MERCHANT_NUMBER

### "Transaction model not found"

If using Generic Mode, make sure you've disabled models:
```env
SUMIT_TRANSACTION_MODEL=null
```

### Events not firing

Make sure you've registered listeners in `EventServiceProvider`:
```php
protected $listen = [
    PaymentCompleted::class => [YourListener::class],
];
```

---

## Support

For issues, please check:
1. [README.md](README.md)
2. [GENERIC_ARCHITECTURE.md](GENERIC_ARCHITECTURE.md)
3. [INTEGRATION_EXAMPLES.md](INTEGRATION_EXAMPLES.md)
4. GitHub Issues

---

**Need help?** Create an issue on GitHub!
