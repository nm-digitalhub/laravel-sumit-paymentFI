<?php

namespace NmDigitalHub\LaravelSumitPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public string $orderId;
    public string $reason;
    public array $response;
    public ?int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $orderId, string $reason, array $response, ?int $userId = null)
    {
        $this->orderId = $orderId;
        $this->reason = $reason;
        $this->response = $response;
        $this->userId = $userId;
    }
}
