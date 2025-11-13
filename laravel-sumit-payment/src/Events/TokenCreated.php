<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sumit\LaravelPayment\Models\PaymentToken;

class TokenCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PaymentToken $token;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentToken $token)
    {
        $this->token = $token;
    }
}
