<?php

namespace NmDigitalHub\LaravelSumitPayment\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('sumit-payment.webhook_secret');

        // If no secret is configured, allow the request
        if (empty($webhookSecret)) {
            return $next($request);
        }

        $signature = $request->header('X-Sumit-Signature');
        
        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
