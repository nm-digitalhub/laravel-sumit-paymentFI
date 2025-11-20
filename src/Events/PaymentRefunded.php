<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Refunded Event
 * 
 * Fired when a payment is refunded.
 * This event uses primitive data and is completely model-agnostic.
 */
class PaymentRefunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly float $refundAmount,
        public readonly bool $isPartial = false,
        public readonly ?string $refundDocumentId = null,
        public readonly ?string $reason = null,
        public readonly array $metadata = []
    ) {}
}
