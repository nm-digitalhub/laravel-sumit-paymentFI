<?php

namespace NmDigitalHub\LaravelOfficeGuy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array processPayment(array $paymentData)
 * @method static array refundPayment(\NmDigitalHub\LaravelOfficeGuy\Models\Payment $payment, float $amount = null)
 * @method static array createToken(array $tokenData)
 * @method static \Illuminate\Database\Eloquent\Collection getUserTokens(int $userId)
 * @method static \NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken|null getUserDefaultToken(int $userId)
 * @method static array updateStock(bool $forceSync = false)
 * @method static bool isCurrencySupported(string $currency)
 * @method static void writeToLog(string $message, string $level = 'info')
 *
 * @see \NmDigitalHub\LaravelOfficeGuy\Services\PaymentService
 * @see \NmDigitalHub\LaravelOfficeGuy\Services\TokenService
 * @see \NmDigitalHub\LaravelOfficeGuy\Services\StockService
 * @see \NmDigitalHub\LaravelOfficeGuy\Services\OfficeGuyApiService
 */
class OfficeGuy extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'officeguy';
    }
}
