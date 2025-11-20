<?php

namespace NmDigitalHub\LaravelSumitPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessing
{
    use Dispatchable, SerializesModels;

    public string $orderId;
    public array $paymentData;
    public ?int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $orderId, array $paymentData, ?int $userId = null)
    {
        $this->orderId = $orderId;
        $this->paymentData = $paymentData;
        $this->userId = $userId;
    }
}
