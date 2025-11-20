<?php

namespace Sumit\LaravelPayment\DTO;

/**
 * Payment Response Data Transfer Object
 * 
 * Represents the response from a payment operation.
 * This DTO is completely independent of any models.
 */
class PaymentResponse
{
    public function __construct(
        public readonly string $transactionId,
        public readonly ?string $paymentUrl = null,
        public readonly string $status = 'pending',
        public readonly ?string $documentId = null,
        public readonly ?string $authorizationNumber = null,
        public readonly ?string $lastFourDigits = null,
        public readonly ?string $customerId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}

    /**
     * Check if payment was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' || $this->status === 'success';
    }

    /**
     * Check if payment failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed' || $this->errorMessage !== null;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'processing';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'payment_url' => $this->paymentUrl,
            'status' => $this->status,
            'document_id' => $this->documentId,
            'authorization_number' => $this->authorizationNumber,
            'last_four_digits' => $this->lastFourDigits,
            'customer_id' => $this->customerId,
            'error_message' => $this->errorMessage,
            'raw_response' => $this->rawResponse,
        ];
    }
}
