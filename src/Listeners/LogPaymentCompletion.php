<?php

namespace Sumit\LaravelPayment\Listeners;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;

class LogPaymentCompletion
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        Log::info('Payment completed', [
            'transaction_id' => $event->transaction->id,
            'amount' => $event->transaction->amount,
            'user_id' => $event->transaction->user_id,
        ]);
    }
}
