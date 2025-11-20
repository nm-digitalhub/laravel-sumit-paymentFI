<?php

namespace NmDigitalHub\LaravelSumitPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array processPayment(array $paymentData)
 * @method static array processRefund(string $orderId, float $amount, ?string $description = null)
 * 
 * @see \NmDigitalHub\LaravelSumitPayment\Services\PaymentService
 */
class SumitPayment extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'sumit-payment';
    }
}
