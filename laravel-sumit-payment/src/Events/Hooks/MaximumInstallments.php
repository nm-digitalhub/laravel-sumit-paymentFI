<?php

namespace NmDigitalHub\LaravelSumitPayment\Events\Hooks;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hook event for customizing maximum installments.
 * 
 * Similar to WooCommerce's 'sumit_maximum_installments' filter.
 * 
 * Usage in listener:
 * public function handle(MaximumInstallments $event) {
 *     // Modify $event->maxPayments based on custom logic
 *     if ($event->orderValue > 1000) {
 *         $event->maxPayments = 24;
 *     }
 * }
 */
class MaximumInstallments
{
    use Dispatchable;

    public int $maxPayments;
    public float $orderValue;

    /**
     * Create a new event instance.
     */
    public function __construct(int $maxPayments, float $orderValue)
    {
        $this->maxPayments = $maxPayments;
        $this->orderValue = $orderValue;
    }

    /**
     * Get the modified value.
     */
    public function getValue(): int
    {
        return $this->maxPayments;
    }

    /**
     * Set the maximum payments.
     */
    public function setMaxPayments(int $maxPayments): void
    {
        $this->maxPayments = $maxPayments;
    }
}
