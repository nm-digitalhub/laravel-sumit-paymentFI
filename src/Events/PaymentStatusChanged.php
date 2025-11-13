<?php

namespace Sumit\LaravelPayment\Events;

use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusChanged
{
    use Dispatchable, SerializesModels;

    public Transaction $transaction;
    public string $newStatus;
    public string $oldStatus;

    public function __construct(Transaction $transaction, string $newStatus, string $oldStatus)
    {
        $this->transaction = $transaction;
        $this->newStatus = $newStatus;
        $this->oldStatus = $oldStatus;
    }
}
