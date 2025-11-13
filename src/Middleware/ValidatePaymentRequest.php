<?php

namespace Sumit\LaravelPayment\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate that SUMIT is properly configured
        if (!config('sumit-payment.company_id') || !config('sumit-payment.api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway is not properly configured',
            ], 500);
        }

        // Validate required payment parameters
        if ($request->isMethod('post') && $request->is('*/payment/process')) {
            $required = ['amount', 'customer_name', 'customer_email'];
            foreach ($required as $field) {
                if (!$request->has($field)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Missing required field: {$field}",
                    ], 422);
                }
            }

            // Validate amount
            if ($request->input('amount') <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount must be greater than zero',
                ], 422);
            }
        }

        return $next($request);
    }
}
