# SUMIT Payment Laravel Package - Usage Examples

## Table of Contents

1. [Basic Payment Processing](#basic-payment-processing)
2. [Payment with Installments](#payment-with-installments)
3. [Using Saved Payment Tokens](#using-saved-payment-tokens)
4. [Subscription Payments](#subscription-payments)
5. [Creating Invoices](#creating-invoices)
6. [Processing Refunds](#processing-refunds)
7. [Event Listeners](#event-listeners)
8. [Custom Hooks](#custom-hooks)

## Basic Payment Processing

### Simple One-Time Payment

```php
<?php

use NmDigitalHub\LaravelSumitPayment\Facades\SumitPayment;

public function processCheckout(Request $request)
{
    $paymentData = [
        'order_id' => 'ORD-' . time(),
        'user_id' => auth()->id(),
        'amount' => 250.00,
        'currency' => 'ILS',
        'items' => [
            [
                'Item' => [
                    'Name' => 'Premium Subscription',
                    'SKU' => 'SUB-001',
                    'SearchMode' => 'Automatic',
                ],
                'Quantity' => 1,
                'UnitPrice' => 250.00,
                'Currency' => 'ILS',
            ],
        ],
        'customer' => [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'zip_code' => $request->input('zip_code'),
        ],
        'description' => 'Premium subscription purchase',
        'vat_rate' => '17',
    ];

    $result = SumitPayment::processPayment($paymentData);

    if ($result['success']) {
        return redirect()->route('payment.success')
            ->with('transaction_id', $result['transaction']->id);
    }

    return back()->withErrors(['payment' => $result['error']]);
}
```

## Payment with Installments

```php
<?php

public function processInstallmentPayment(Request $request)
{
    $paymentData = [
        'order_id' => 'ORD-' . time(),
        'user_id' => auth()->id(),
        'amount' => 1200.00,
        'currency' => 'ILS',
        'payments_count' => 6, // 6 monthly installments
        'items' => [
            [
                'Item' => [
                    'Name' => 'Laptop Computer',
                    'SearchMode' => 'Automatic',
                ],
                'Quantity' => 1,
                'UnitPrice' => 1200.00,
                'Currency' => 'ILS',
            ],
        ],
        'customer' => [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ],
    ];

    $result = SumitPayment::processPayment($paymentData);

    if ($result['success']) {
        // Access installment information
        $transaction = $result['transaction'];
        $firstPayment = $transaction->first_payment_amount;
        $otherPayments = $transaction->non_first_payment_amount;
        
        return view('payment.success', compact('transaction'));
    }

    return back()->withErrors(['payment' => $result['error']]);
}
```

## Using Saved Payment Tokens

### Saving a Payment Token

```php
<?php

use NmDigitalHub\LaravelSumitPayment\Services\TokenService;

public function savePaymentMethod(Request $request)
{
    $tokenService = app(TokenService::class);

    $result = $tokenService->generateToken([
        'card_number' => $request->input('card_number'),
        'cvv' => $request->input('cvv'),
        'expiry_month' => $request->input('expiry_month'),
        'expiry_year' => $request->input('expiry_year'),
        'citizen_id' => $request->input('citizen_id'),
        'is_default' => true,
    ], auth()->id());

    if ($result['success']) {
        return redirect()->route('payment-methods')
            ->with('success', 'Payment method saved successfully');
    }

    return back()->withErrors(['token' => $result['error']]);
}
```

### Paying with Saved Token

```php
<?php

public function payWithSavedCard(Request $request)
{
    $paymentData = [
        'order_id' => 'ORD-' . time(),
        'user_id' => auth()->id(),
        'amount' => 150.00,
        'currency' => 'ILS',
        'payment_token_id' => $request->input('token_id'),
        'items' => [/* ... */],
        'customer' => [/* ... */],
    ];

    $result = SumitPayment::processPayment($paymentData);

    return $result['success'] 
        ? redirect()->route('payment.success')
        : back()->withErrors(['payment' => $result['error']]);
}
```

## Subscription Payments

```php
<?php

public function processSubscription(Request $request)
{
    $paymentData = [
        'order_id' => 'SUB-' . time(),
        'user_id' => auth()->id(),
        'amount' => 99.00,
        'currency' => 'ILS',
        'is_subscription' => true,
        'save_token' => true, // Save token for future charges
        'items' => [
            [
                'Item' => [
                    'Name' => 'Monthly Subscription',
                    'SearchMode' => 'Automatic',
                ],
                'Quantity' => 1,
                'UnitPrice' => 99.00,
                'Currency' => 'ILS',
                'Duration_Months' => 1,
                'Recurrence' => 12, // 12 monthly payments
            ],
        ],
        'customer' => [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ],
    ];

    $result = SumitPayment::processPayment($paymentData);

    if ($result['success']) {
        // Store subscription details in your database
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'transaction_id' => $result['transaction']->id,
            'payment_token_id' => $result['transaction']->payment_token_id,
            'status' => 'active',
        ]);

        return redirect()->route('subscription.success');
    }

    return back()->withErrors(['payment' => $result['error']]);
}
```

## Creating Invoices

### Create Invoice After External Payment

```php
<?php

use NmDigitalHub\LaravelSumitPayment\Services\InvoiceService;

public function createInvoiceForPaypalPayment(Request $request)
{
    $invoiceService = app(InvoiceService::class);

    $result = $invoiceService->createInvoice([
        'order_id' => $request->input('order_id'),
        'type' => 'invoice',
        'currency' => 'ILS',
        'total_amount' => $request->input('amount'),
        'items' => [
            [
                'Item' => [
                    'Name' => 'Product Purchase',
                    'SearchMode' => 'Automatic',
                ],
                'Quantity' => 1,
                'DocumentCurrency_UnitPrice' => $request->input('amount'),
            ],
        ],
        'customer' => [
            'Name' => $request->input('customer_name'),
            'EmailAddress' => $request->input('customer_email'),
        ],
        'payments' => [
            [
                'Details_Other' => [
                    'Type' => 'PayPal',
                    'Description' => 'PayPal Transaction: ' . $request->input('paypal_transaction_id'),
                    'DueDate' => now()->format('Y-m-d\TH:i:s'),
                ],
            ],
        ],
    ]);

    if ($result['success']) {
        return response()->json([
            'document_id' => $result['document_id'],
        ]);
    }

    return response()->json(['error' => $result['error']], 400);
}
```

## Processing Refunds

### Full Refund

```php
<?php

public function processFullRefund($orderId)
{
    $transaction = Transaction::where('order_id', $orderId)->first();

    $result = SumitPayment::processRefund(
        orderId: $orderId,
        amount: $transaction->amount,
        description: 'Full refund for order ' . $orderId
    );

    if ($result['success']) {
        $transaction->update(['status' => 'refunded']);
        
        return redirect()->route('orders.show', $orderId)
            ->with('success', 'Refund processed successfully');
    }

    return back()->withErrors(['refund' => $result['error']]);
}
```

### Partial Refund

```php
<?php

public function processPartialRefund(Request $request, $orderId)
{
    $refundAmount = $request->input('amount');

    $result = SumitPayment::processRefund(
        orderId: $orderId,
        amount: $refundAmount,
        description: 'Partial refund: ' . $request->input('reason')
    );

    if ($result['success']) {
        return response()->json([
            'success' => true,
            'refund_id' => $result['refund_id'],
        ]);
    }

    return response()->json(['error' => $result['error']], 400);
}
```

## Event Listeners

### Listen to Payment Events

Create an event listener:

```php
<?php

namespace App\Listeners;

use NmDigitalHub\LaravelSumitPayment\Events\PaymentCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentConfirmation implements ShouldQueue
{
    public function handle(PaymentCompleted $event)
    {
        $transaction = $event->transaction;
        
        // Send confirmation email
        Mail::to($transaction->user->email)
            ->send(new PaymentConfirmationMail($transaction));
        
        // Log to analytics
        Analytics::track('payment_completed', [
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
        ]);
        
        // Update order status
        Order::where('id', $transaction->order_id)
            ->update(['status' => 'paid']);
    }
}
```

Register in `EventServiceProvider`:

```php
<?php

use NmDigitalHub\LaravelSumitPayment\Events\PaymentCompleted;
use NmDigitalHub\LaravelSumitPayment\Events\PaymentFailed;
use App\Listeners\SendPaymentConfirmation;
use App\Listeners\HandlePaymentFailure;

protected $listen = [
    PaymentCompleted::class => [
        SendPaymentConfirmation::class,
    ],
    PaymentFailed::class => [
        HandlePaymentFailure::class,
    ],
];
```

## Custom Hooks

### Custom Maximum Installments Logic

```php
<?php

use NmDigitalHub\LaravelSumitPayment\Events\PaymentProcessing;

Event::listen(PaymentProcessing::class, function ($event) {
    $amount = $event->paymentData['amount'];
    
    // Custom logic: Premium customers get more installments
    $user = User::find($event->userId);
    if ($user && $user->is_premium) {
        // Modify payment data to allow more installments
        // This would require extending the PaymentService
    }
});
```

### Custom Customer Fields

```php
<?php

// In a service provider or listener
Event::listen(PaymentProcessing::class, function ($event) {
    // Add custom fields to customer data
    if (isset($event->paymentData['customer'])) {
        $event->paymentData['customer']['custom_field'] = 'custom_value';
    }
});
```

### Custom VAT Logic

```php
<?php

public function calculateCustomVat($amount, $customerCountry)
{
    // Custom VAT calculation based on country
    $vatRates = [
        'IL' => 17,
        'US' => 0,
        'EU' => 21,
    ];
    
    return $vatRates[$customerCountry] ?? 0;
}
```

## Advanced: Redirect Payment Flow

```php
<?php

public function initiateRedirectPayment(Request $request)
{
    // Set PCI compliance to redirect mode in config
    config(['sumit-payment.pci_compliance' => 'redirect']);

    $paymentData = [
        'order_id' => 'ORD-' . time(),
        'amount' => 100.00,
        'currency' => 'ILS',
        'redirect_url' => route('payment.callback'),
        'items' => [/* ... */],
        'customer' => [/* ... */],
    ];

    $result = SumitPayment::processPayment($paymentData);

    if ($result['success'] && isset($result['redirect_url'])) {
        return redirect($result['redirect_url']);
    }

    return back()->withErrors(['payment' => 'Failed to initiate payment']);
}

public function handleCallback(Request $request)
{
    $paymentId = $request->input('OG-PaymentID');
    $orderId = $request->input('OG-OrderID');
    
    // The webhook will handle updating the transaction
    return view('payment.processing', compact('orderId'));
}
```
