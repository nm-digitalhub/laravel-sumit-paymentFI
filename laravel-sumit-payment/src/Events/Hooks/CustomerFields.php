<?php

namespace NmDigitalHub\LaravelSumitPayment\Events\Hooks;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hook event for customizing customer fields.
 * 
 * Similar to WooCommerce's 'sumit_customer_fields' filter.
 * 
 * Usage in listener:
 * public function handle(CustomerFields $event) {
 *     $event->customer['CustomField'] = 'value';
 *     $event->customer['BillingLastName'] = $event->orderData['billing_last_name'] ?? '';
 * }
 */
class CustomerFields
{
    use Dispatchable;

    public array $customer;
    public array $orderData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $customer, array $orderData)
    {
        $this->customer = $customer;
        $this->orderData = $orderData;
    }

    /**
     * Get the modified customer data.
     */
    public function getValue(): array
    {
        return $this->customer;
    }

    /**
     * Set a customer field.
     */
    public function setField(string $key, $value): void
    {
        $this->customer[$key] = $value;
    }

    /**
     * Remove a customer field.
     */
    public function removeField(string $key): void
    {
        unset($this->customer[$key]);
    }
}
