<?php

namespace NmDigitalHub\LaravelOfficeGuy\Events;

use NmDigitalHub\LaravelOfficeGuy\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessed
{
    use Dispatchable, SerializesModels;

    public Payment $payment;
    public array $response;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, array $response)
    {
        $this->payment = $payment;
        $this->response = $response;
    }
}
