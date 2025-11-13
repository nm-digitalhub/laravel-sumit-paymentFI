<?php

namespace NmDigitalHub\LaravelOfficeGuy\Listeners;

use NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed;
use Illuminate\Support\Facades\Log;

class LogSuccessfulPayment
{
    /**
     * Handle the event.
     */
    public function handle(PaymentProcessed $event): void
    {
        Log::info('[OfficeGuy] Payment processed successfully', [
            'payment_id' => $event->payment->id,
            'transaction_id' => $event->payment->transaction_id,
            'amount' => $event->payment->amount,
            'currency' => $event->payment->currency,
            'user_id' => $event->payment->user_id,
            'order_id' => $event->payment->order_id,
        ]);
    }
}
