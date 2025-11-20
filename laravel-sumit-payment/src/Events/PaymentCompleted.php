<?php

namespace NmDigitalHub\LaravelSumitPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NmDigitalHub\LaravelSumitPayment\Models\Transaction;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public Transaction $transaction;
    public array $response;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction, array $response)
    {
        $this->transaction = $transaction;
        $this->response = $response;
    }
}
