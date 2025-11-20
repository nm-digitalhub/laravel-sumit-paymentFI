<?php

namespace NmDigitalHub\LaravelSumitPayment\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCurrency
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currency = $request->input('currency');
        
        if (!$currency) {
            return $next($request);
        }

        $supportedCurrencies = config('sumit-payment.supported_currencies', []);

        if (!in_array($currency, $supportedCurrencies)) {
            return response()->json([
                'success' => false,
                'error' => "Currency {$currency} is not supported",
                'supported_currencies' => $supportedCurrencies,
            ], 422);
        }

        return $next($request);
    }
}
