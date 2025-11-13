<?php

namespace Sumit\LaravelPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array processPayment(array $paymentData)
 * @method static array processPaymentWithToken(array $paymentData, int $tokenId)
 * @method static array tokenizeCard(array $cardData, ?int $userId = null)
 * @method static array processRedirectCallback(string $transactionId, array $callbackData)
 *
 * @see \Sumit\LaravelPayment\Services\PaymentService
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
