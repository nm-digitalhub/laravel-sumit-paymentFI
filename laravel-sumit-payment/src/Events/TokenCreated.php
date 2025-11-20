<?php

namespace NmDigitalHub\LaravelSumitPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;

class TokenCreated
{
    use Dispatchable, SerializesModels;

    public PaymentToken $token;
    public ?int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentToken $token, ?int $userId = null)
    {
        $this->token = $token;
        $this->userId = $userId;
    }
}
