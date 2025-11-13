<?php

namespace Sumit\LaravelPayment\Events;

use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public Transaction $transaction;
    public float $refundAmount;
    public string $reason;

    public function __construct(Transaction $transaction, float $refundAmount, string $reason = '')
    {
        $this->transaction = $transaction;
        $this->refundAmount = $refundAmount;
        $this->reason = $reason;
    }
}
