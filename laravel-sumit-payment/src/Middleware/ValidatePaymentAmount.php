<?php

namespace NmDigitalHub\LaravelSumitPayment\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentAmount
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $amount = $request->input('amount');
        $paymentsCount = $request->input('payments_count', 1);
        
        if (!$amount) {
            return $next($request);
        }

        // Validate minimum amount
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Payment amount must be greater than zero',
            ], 422);
        }

        // Validate installments
        if ($paymentsCount > 1) {
            $minAmountForPayments = config('sumit-payment.min_amount_for_payments', 0);
            
            if ($minAmountForPayments > 0 && $amount < $minAmountForPayments) {
                return response()->json([
                    'success' => false,
                    'error' => "Minimum amount for installments is {$minAmountForPayments}",
                ], 422);
            }

            $minAmountPerPayment = config('sumit-payment.min_amount_per_payment', 0);
            
            if ($minAmountPerPayment > 0 && ($amount / $paymentsCount) < $minAmountPerPayment) {
                return response()->json([
                    'success' => false,
                    'error' => "Each installment must be at least {$minAmountPerPayment}",
                ], 422);
            }

            $maxPayments = config('sumit-payment.max_payments', 12);
            
            if ($paymentsCount > $maxPayments) {
                return response()->json([
                    'success' => false,
                    'error' => "Maximum number of installments is {$maxPayments}",
                ], 422);
            }
        }

        return $next($request);
    }
}
