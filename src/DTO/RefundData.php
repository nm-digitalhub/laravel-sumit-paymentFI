<?php

namespace Sumit\LaravelPayment\DTO;

/**
 * Refund Data Transfer Object
 * 
 * Represents refund request data in a type-safe manner.
 * This DTO is completely independent of any models.
 */
class RefundData
{
    public function __construct(
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly ?string $reason = null,
        public readonly bool $isPartial = false,
        public readonly array $metadata = []
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: $data['transaction_id'],
            amount: $data['amount'],
            reason: $data['reason'] ?? null,
            isPartial: $data['is_partial'] ?? false,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'is_partial' => $this->isPartial,
            'metadata' => $this->metadata,
        ];
    }
}
