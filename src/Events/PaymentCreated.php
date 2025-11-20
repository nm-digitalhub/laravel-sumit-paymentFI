<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sumit\LaravelPayment\DTO\PaymentData;
use Sumit\LaravelPayment\DTO\PaymentResponse;

/**
 * Payment Created Event
 * 
 * Fired when a payment is initiated.
 * This event uses DTOs and is completely model-agnostic.
 */
class PaymentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly PaymentResponse $response,
        public readonly PaymentData $request
    ) {}
}
