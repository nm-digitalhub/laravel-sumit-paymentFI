<?php

namespace Sumit\LaravelPayment\Events;

use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Models\PaymentToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    public Transaction $subscription;
    public PaymentToken $token;

    public function __construct(Transaction $subscription, PaymentToken $token)
    {
        $this->subscription = $subscription;
        $this->token = $token;
    }
}
