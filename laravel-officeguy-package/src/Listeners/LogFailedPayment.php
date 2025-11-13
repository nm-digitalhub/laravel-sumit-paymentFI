<?php

namespace NmDigitalHub\LaravelOfficeGuy\Listeners;

use NmDigitalHub\LaravelOfficeGuy\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

class LogFailedPayment
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        Log::warning('[OfficeGuy] Payment failed', [
            'payment_id' => $event->payment->id,
            'amount' => $event->payment->amount,
            'currency' => $event->payment->currency,
            'user_id' => $event->payment->user_id,
            'order_id' => $event->payment->order_id,
            'error' => $event->payment->error_message,
        ]);
    }
}
