<?php

namespace Sumit\LaravelPayment\Listeners;

use Sumit\LaravelPayment\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

class LogPaymentFailure
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        Log::error('Payment failed', [
            'transaction_id' => $event->transaction->id,
            'amount' => $event->transaction->amount,
            'user_id' => $event->transaction->user_id,
            'error' => $event->errorMessage,
        ]);
    }
}
