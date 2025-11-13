<?php

namespace NmDigitalHub\LaravelOfficeGuy\Events;

use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenCreated
{
    use Dispatchable, SerializesModels;

    public PaymentToken $token;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentToken $token)
    {
        $this->token = $token;
    }
}
