<?php

namespace NmDigitalHub\LaravelOfficeGuy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get signature from request
        $signature = $request->header('X-OG-Signature');

        // If signature verification is disabled in config, skip
        if (!config('officeguy.webhook.verify_signature', true)) {
            return $next($request);
        }

        // If no signature present, reject
        if (!$signature) {
            Log::warning('[OfficeGuy] Webhook request without signature');
            
            return response()->json([
                'success' => false,
                'error' => 'Missing signature',
            ], 401);
        }

        // Verify signature
        $payload = $request->getContent();
        $expectedSignature = $this->calculateSignature($payload);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('[OfficeGuy] Invalid webhook signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Calculate expected signature.
     *
     * @param  string  $payload
     * @return string
     */
    protected function calculateSignature(string $payload): string
    {
        $secret = config('officeguy.api_private_key');
        return hash_hmac('sha256', $payload, $secret);
    }
}
