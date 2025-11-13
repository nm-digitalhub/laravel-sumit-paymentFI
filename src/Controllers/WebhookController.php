<?php

namespace Sumit\LaravelPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Events\WebhookReceived;
use Sumit\LaravelPayment\Events\PaymentStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from SUMIT.
     */
    public function handle(Request $request)
    {
        try {
            // Log incoming webhook
            Log::info('SUMIT Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Validate webhook signature if available
            if (!$this->validateWebhook($request)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Extract webhook data
            $webhookData = $request->all();
            $eventType = $webhookData['EventType'] ?? $webhookData['event_type'] ?? 'unknown';

            // Dispatch generic webhook event
            Event::dispatch(new WebhookReceived($eventType, $webhookData));

            // Handle specific webhook types
            $response = match($eventType) {
                'payment.completed', 'PaymentCompleted' => $this->handlePaymentCompleted($webhookData),
                'payment.failed', 'PaymentFailed' => $this->handlePaymentFailed($webhookData),
                'payment.refunded', 'PaymentRefunded' => $this->handlePaymentRefunded($webhookData),
                'payment.authorized', 'PaymentAuthorized' => $this->handlePaymentAuthorized($webhookData),
                'subscription.charged', 'SubscriptionCharged' => $this->handleSubscriptionCharged($webhookData),
                'subscription.failed', 'SubscriptionFailed' => $this->handleSubscriptionFailed($webhookData),
                default => $this->handleGenericWebhook($webhookData),
            };

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle payment completed webhook.
     */
    protected function handlePaymentCompleted(array $data): void
    {
        $transactionId = $data['TransactionID'] ?? $data['transaction_id'] ?? null;
        
        if ($transactionId) {
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            
            if ($transaction && $transaction->status !== 'completed') {
                $oldStatus = $transaction->status;
                
                $transaction->update([
                    'status' => 'completed',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_completed_at' => now()->toDateTimeString(),
                        'webhook_data' => $data,
                    ]),
                ]);

                Event::dispatch(new PaymentStatusChanged($transaction, 'completed', $oldStatus));
            }
        }
    }

    /**
     * Handle payment failed webhook.
     */
    protected function handlePaymentFailed(array $data): void
    {
        $transactionId = $data['TransactionID'] ?? $data['transaction_id'] ?? null;
        
        if ($transactionId) {
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            
            if ($transaction) {
                $errorMessage = $data['ErrorMessage'] ?? $data['error_message'] ?? 'Payment failed';
                $oldStatus = $transaction->status;
                
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_failed_at' => now()->toDateTimeString(),
                        'webhook_data' => $data,
                    ]),
                ]);

                Event::dispatch(new PaymentStatusChanged($transaction, 'failed', $oldStatus));
            }
        }
    }

    /**
     * Handle payment refunded webhook.
     */
    protected function handlePaymentRefunded(array $data): void
    {
        $transactionId = $data['TransactionID'] ?? $data['transaction_id'] ?? null;
        $refundAmount = $data['RefundAmount'] ?? $data['refund_amount'] ?? 0;
        
        if ($transactionId) {
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            
            if ($transaction) {
                $transaction->update([
                    'refund_amount' => ($transaction->refund_amount ?? 0) + $refundAmount,
                    'refund_status' => ($refundAmount >= $transaction->amount) ? 'full' : 'partial',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_refunded_at' => now()->toDateTimeString(),
                        'webhook_data' => $data,
                    ]),
                ]);

                Event::dispatch(new PaymentStatusChanged($transaction, 'refunded', $transaction->status));
            }
        }
    }

    /**
     * Handle payment authorized webhook.
     */
    protected function handlePaymentAuthorized(array $data): void
    {
        $transactionId = $data['TransactionID'] ?? $data['transaction_id'] ?? null;
        
        if ($transactionId) {
            $transaction = Transaction::where('transaction_id', $transactionId)->first();
            
            if ($transaction && $transaction->status !== 'authorized') {
                $oldStatus = $transaction->status;
                
                $transaction->update([
                    'status' => 'authorized',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_authorized_at' => now()->toDateTimeString(),
                        'webhook_data' => $data,
                    ]),
                ]);

                Event::dispatch(new PaymentStatusChanged($transaction, 'authorized', $oldStatus));
            }
        }
    }

    /**
     * Handle subscription charged webhook.
     */
    protected function handleSubscriptionCharged(array $data): void
    {
        $subscriptionId = $data['SubscriptionID'] ?? $data['subscription_id'] ?? null;
        
        if ($subscriptionId) {
            $subscription = Transaction::where('id', $subscriptionId)
                ->where('is_subscription', true)
                ->first();
            
            if ($subscription) {
                $metadata = $subscription->metadata ?? [];
                $metadata['last_webhook_charge'] = now()->toDateTimeString();
                $metadata['webhook_data'] = $data;
                
                $subscription->metadata = $metadata;
                $subscription->save();
            }
        }
    }

    /**
     * Handle subscription failed webhook.
     */
    protected function handleSubscriptionFailed(array $data): void
    {
        $subscriptionId = $data['SubscriptionID'] ?? $data['subscription_id'] ?? null;
        
        if ($subscriptionId) {
            $subscription = Transaction::where('id', $subscriptionId)
                ->where('is_subscription', true)
                ->first();
            
            if ($subscription) {
                $metadata = $subscription->metadata ?? [];
                $metadata['failed_attempts'] = ($metadata['failed_attempts'] ?? 0) + 1;
                $metadata['last_webhook_failure'] = now()->toDateTimeString();
                $metadata['webhook_data'] = $data;
                
                // Cancel subscription after 3 failed attempts
                if ($metadata['failed_attempts'] >= 3) {
                    $subscription->status = 'cancelled';
                }
                
                $subscription->metadata = $metadata;
                $subscription->save();
            }
        }
    }

    /**
     * Handle generic webhook.
     */
    protected function handleGenericWebhook(array $data): void
    {
        // Log for debugging
        Log::info('Generic webhook handled', ['data' => $data]);
    }

    /**
     * Validate webhook signature.
     */
    protected function validateWebhook(Request $request): bool
    {
        // If no signature header is present, skip validation in development
        if (config('sumit-payment.testing_mode', false)) {
            return true;
        }

        // Get signature from header
        $signature = $request->header('X-SUMIT-Signature');
        
        if (!$signature) {
            return true; // Allow if no signature required
        }

        // Validate signature using API key
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, config('sumit-payment.api_key'));

        return hash_equals($expectedSignature, $signature);
    }
}
