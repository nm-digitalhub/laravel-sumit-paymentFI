<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sumit\LaravelPayment\Models\Transaction;

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Transaction $transaction;
    public string $errorMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction, string $errorMessage)
    {
        $this->transaction = $transaction;
        $this->errorMessage = $errorMessage;
    }
}
