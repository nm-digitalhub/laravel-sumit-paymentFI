<?php

namespace Sumit\LaravelPayment\Contracts;

use Sumit\LaravelPayment\DTO\PaymentData;
use Sumit\LaravelPayment\DTO\PaymentResponse;

/**
 * Payment Gateway Interface
 * 
 * Defines the contract for payment gateway implementations.
 * Users can implement this interface to create custom payment logic.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment
     *
     * @param PaymentData $data Payment data
     * @return PaymentResponse Payment response
     */
    public function createPayment(PaymentData $data): PaymentResponse;

    /**
     * Get transaction status
     *
     * @param string $transactionId Transaction ID
     * @return PaymentResponse Payment response
     */
    public function getTransactionStatus(string $transactionId): PaymentResponse;

    /**
     * Refund a transaction
     *
     * @param string $transactionId Transaction ID
     * @param float $amount Refund amount
     * @param string|null $reason Refund reason
     * @return PaymentResponse Payment response
     */
    public function refund(string $transactionId, float $amount, ?string $reason = null): PaymentResponse;
}
