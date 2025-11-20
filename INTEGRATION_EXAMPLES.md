# Integration Examples

This document provides real-world examples of integrating the SUMIT Payment Package with different types of applications.

---

## Example 1: E-commerce Store (Using Your Own Models)

### Project Structure
```
app/
├── Models/
│   ├── Order.php
│   └── Payment.php
└── Listeners/
    └── Payment/
        ├── StoreOrderPayment.php
        └── SendOrderConfirmation.php
```

### Configuration (.env)
```env
# Disable package models - use your own
SUMIT_TRANSACTION_MODEL=null
SUMIT_TOKEN_MODEL=null
SUMIT_CUSTOMER_MODEL=null

# SUMIT API credentials
SUMIT_COMPANY_ID=xxx
SUMIT_API_KEY=xxx
SUMIT_MERCHANT_NUMBER=xxx
```

### Your Payment Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

### Event Listener
```php
<?php

namespace App\Listeners\Payment;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;

class StoreOrderPayment
{
    public function handle(PaymentCompleted $event): void
    {
        // Extract order ID from metadata
        $orderId = $event->metadata['order_id'] ?? null;
        
        if (!$orderId) {
            return;
        }

        // Store payment in your database
        $payment = Payment::create([
            'order_id' => $orderId,
            'gateway' => 'sumit',
            'gateway_transaction_id' => $event->transactionId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'status' => 'completed',
            'metadata' => $event->metadata,
        ]);

        // Update order status
        $order = Order::find($orderId);
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Send confirmation email
        Mail::to($order->customer->email)->send(new OrderConfirmation($order));
    }
}
```

### Event Service Provider
```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Listeners\Payment\StoreOrderPayment;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentCompleted::class => [
            StoreOrderPayment::class,
        ],
    ];
}
```

### Controller
```php
<?php

namespace App\Http\Controllers;

use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;
use App\Models\Order;

class CheckoutController extends Controller
{
    public function processPayment(Request $request, GenericPaymentService $payment)
    {
        $order = Order::find($request->order_id);

        // Create payment data
        $paymentData = new PaymentData(
            amount: $order->total,
            currency: 'ILS',
            customerName: $order->customer_name,
            customerEmail: $order->customer_email,
            description: "Order #{$order->id}",
            metadata: [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
            ]
        );

        // Process payment (fires events automatically)
        $response = $payment->createPayment($paymentData);

        if ($response->isSuccessful()) {
            return redirect()->route('order.success', $order);
        }

        return redirect()->back()->withErrors(['payment' => $response->errorMessage]);
    }
}
```

---

## Example 2: SaaS Subscription Platform

### Project Structure
```
app/
├── Models/
│   ├── User.php
│   ├── Subscription.php
│   └── SubscriptionPayment.php
└── Listeners/
    └── Subscription/
        └── ActivateSubscription.php
```

### Subscription Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
```

### Event Listener
```php
<?php

namespace App\Listeners\Subscription;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;

class ActivateSubscription
{
    public function handle(PaymentCompleted $event): void
    {
        $subscriptionId = $event->metadata['subscription_id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        // Record payment
        SubscriptionPayment::create([
            'subscription_id' => $subscriptionId,
            'transaction_id' => $event->transactionId,
            'amount' => $event->amount,
            'status' => 'completed',
        ]);

        // Activate or renew subscription
        $subscription = Subscription::find($subscriptionId);
        
        if ($subscription->status === 'pending') {
            // First payment - activate subscription
            $subscription->update([
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        } else {
            // Renewal payment
            $subscription->update([
                'current_period_start' => $subscription->current_period_end,
                'current_period_end' => $subscription->current_period_end->addMonth(),
            ]);
        }

        // Grant access to user
        $subscription->user->grantPremiumAccess();
    }
}
```

### Subscription Controller
```php
<?php

namespace App\Http\Controllers;

use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;
use App\Models\Subscription;
use App\Models\Plan;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request, GenericPaymentService $payment)
    {
        $plan = Plan::find($request->plan_id);
        
        // Create subscription record
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        // Process initial payment
        $paymentData = new PaymentData(
            amount: $plan->price,
            currency: 'ILS',
            customerName: auth()->user()->name,
            customerEmail: auth()->user()->email,
            description: "Subscription to {$plan->name}",
            isSubscription: true,
            metadata: [
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
            ]
        );

        $response = $payment->createPayment($paymentData);

        return response()->json([
            'success' => $response->isSuccessful(),
            'subscription' => $subscription,
        ]);
    }
}
```

---

## Example 3: Donation Platform

### Project Structure
```
app/
├── Models/
│   ├── Campaign.php
│   └── Donation.php
└── Listeners/
    └── Donation/
        └── RecordDonation.php
```

### Donation Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'campaign_id',
        'donor_name',
        'donor_email',
        'amount',
        'currency',
        'transaction_id',
        'receipt_id',
        'is_anonymous',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
```

### Event Listener
```php
<?php

namespace App\Listeners\Donation;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Models\Donation;
use App\Models\Campaign;
use App\Mail\DonationReceipt;
use Illuminate\Support\Facades\Mail;

class RecordDonation
{
    public function handle(PaymentCompleted $event): void
    {
        $campaignId = $event->metadata['campaign_id'] ?? null;

        if (!$campaignId) {
            return;
        }

        // Record donation
        $donation = Donation::create([
            'campaign_id' => $campaignId,
            'donor_name' => $event->metadata['donor_name'],
            'donor_email' => $event->metadata['donor_email'],
            'amount' => $event->amount,
            'currency' => $event->currency,
            'transaction_id' => $event->transactionId,
            'receipt_id' => $event->documentId,
            'is_anonymous' => $event->metadata['is_anonymous'] ?? false,
        ]);

        // Update campaign total
        $campaign = Campaign::find($campaignId);
        $campaign->increment('total_raised', $event->amount);
        $campaign->increment('donors_count');

        // Send tax receipt
        Mail::to($donation->donor_email)->send(new DonationReceipt($donation));
    }
}
```

### Donation Controller
```php
<?php

namespace App\Http\Controllers;

use Sumit\LaravelPayment\Services\GenericPaymentService;
use Sumit\LaravelPayment\DTO\PaymentData;
use App\Models\Campaign;

class DonationController extends Controller
{
    public function donate(Request $request, GenericPaymentService $payment)
    {
        $campaign = Campaign::find($request->campaign_id);

        $paymentData = new PaymentData(
            amount: $request->amount,
            currency: 'ILS',
            customerName: $request->donor_name,
            customerEmail: $request->donor_email,
            description: "Donation to {$campaign->name}",
            isDonation: true,
            metadata: [
                'campaign_id' => $campaign->id,
                'donor_name' => $request->donor_name,
                'donor_email' => $request->donor_email,
                'is_anonymous' => $request->is_anonymous ?? false,
            ]
        );

        $response = $payment->createPayment($paymentData);

        return response()->json([
            'success' => $response->isSuccessful(),
            'message' => $response->isSuccessful() 
                ? 'Thank you for your donation!' 
                : $response->errorMessage,
        ]);
    }
}
```

---

## Example 4: Marketplace with Escrow

### Project Structure
```
app/
├── Models/
│   ├── Transaction.php
│   └── Escrow.php
└── Listeners/
    └── Marketplace/
        └── HoldInEscrow.php
```

### Escrow Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    protected $fillable = [
        'transaction_id',
        'buyer_id',
        'seller_id',
        'amount',
        'status',
        'held_until',
    ];

    protected $casts = [
        'held_until' => 'datetime',
    ];
}
```

### Event Listener
```php
<?php

namespace App\Listeners\Marketplace;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use App\Models\Escrow;
use App\Models\Transaction as MarketplaceTransaction;

class HoldInEscrow
{
    public function handle(PaymentCompleted $event): void
    {
        $transactionId = $event->metadata['marketplace_transaction_id'] ?? null;

        if (!$transactionId) {
            return;
        }

        $transaction = MarketplaceTransaction::find($transactionId);

        // Create escrow hold
        Escrow::create([
            'transaction_id' => $transactionId,
            'buyer_id' => $transaction->buyer_id,
            'seller_id' => $transaction->seller_id,
            'amount' => $event->amount,
            'status' => 'held',
            'held_until' => now()->addDays(7),
        ]);

        // Update transaction status
        $transaction->update([
            'status' => 'in_escrow',
            'payment_transaction_id' => $event->transactionId,
        ]);
    }
}
```

---

## Example 5: Using Package Models (Quick Start)

If you want to use the package's built-in models for a quick start:

### Configuration (.env)
```env
# Use package models (default)
SUMIT_TRANSACTION_MODEL=Sumit\LaravelPayment\Models\Transaction
SUMIT_TOKEN_MODEL=Sumit\LaravelPayment\Models\PaymentToken
```

### Controller (Simple Usage)
```php
<?php

namespace App\Http\Controllers;

use Sumit\LaravelPayment\Services\PaymentService;
use Sumit\LaravelPayment\Models\Transaction;

class PaymentController extends Controller
{
    public function process(Request $request, PaymentService $payment)
    {
        // Process payment (stores in package's Transaction model automatically)
        $result = $payment->processPayment([
            'amount' => $request->amount,
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'card_number' => $request->card_number,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'cvv' => $request->cvv,
        ]);

        if ($result['success']) {
            $transaction = $result['transaction'];
            // Use the transaction model
            return view('payment.success', compact('transaction'));
        }

        return redirect()->back()->withErrors(['payment' => $result['message']]);
    }

    public function history()
    {
        // Query transactions using package model
        $transactions = Transaction::where('user_id', auth()->id())
            ->completed()
            ->orderByDesc('created_at')
            ->get();

        return view('payment.history', compact('transactions'));
    }
}
```

---

## Common Patterns

### 1. Passing Custom Data via Metadata
```php
$paymentData = new PaymentData(
    amount: 100,
    metadata: [
        'order_id' => 123,
        'user_id' => 456,
        'source' => 'mobile_app',
        'campaign_code' => 'SPRING2024',
        // Any custom data you need
    ]
);
```

### 2. Handling Multiple Payment Gateways
```php
class UnifiedPaymentListener
{
    public function handle(PaymentCompleted $event): void
    {
        // Same listener works for all gateways
        Payment::create([
            'gateway' => 'sumit', // or 'stripe', 'paypal', etc.
            'transaction_id' => $event->transactionId,
            'amount' => $event->amount,
        ]);
    }
}
```

### 3. Webhooks with Events
```php
// Same listener handles both direct payments and webhooks
Event::listen(PaymentCompleted::class, function($event) {
    // This fires whether payment completed immediately
    // or was updated later via webhook
});
```

---

## Best Practices

1. **Always use metadata for custom data**
2. **Listen to events, don't poll for status**
3. **Use webhooks for async payment flows**
4. **Disable models you don't need**
5. **Keep business logic in your listeners**
6. **Use DTOs for type safety**

---

For more information, see:
- [GENERIC_ARCHITECTURE.md](GENERIC_ARCHITECTURE.md) - Complete architecture guide
- [README.md](README.md) - Installation and setup
