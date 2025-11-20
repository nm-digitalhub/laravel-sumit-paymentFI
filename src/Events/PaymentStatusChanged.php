<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Status Changed Event
 * 
 * Fired when a payment status changes.
 * This event uses primitive data and is completely model-agnostic.
 */
class PaymentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $newStatus,
        public readonly string $oldStatus,
        public readonly array $metadata = []
    ) {}
}
