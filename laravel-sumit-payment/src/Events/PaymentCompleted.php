<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sumit\LaravelPayment\Models\Transaction;

class PaymentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Transaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
