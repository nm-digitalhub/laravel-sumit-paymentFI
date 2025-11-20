<?php

namespace Sumit\LaravelPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sumit\LaravelPayment\Events\WebhookReceived;
use Sumit\LaravelPayment\Events\PaymentCompleted;
use Sumit\LaravelPayment\Events\PaymentFailed;
use Sumit\LaravelPayment\Events\PaymentRefunded;
use Sumit\LaravelPayment\Events\PaymentStatusChanged;
use Sumit\LaravelPayment\Contracts\WebhookHandlerInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Sumit\LaravelPayment\Settings\PaymentSettings;

/**
 * Generic Webhook Controller
 * 
 * This controller is completely model-agnostic.
 * It only fires events and does NOT write to any database.
 * Users can listen to events and handle data storage in their own way.
 */
class GenericWebhookController extends Controller implements WebhookHandlerInterface
{
    public function __construct(
        protected PaymentSettings $settings
    ) {}

    /**
     * Handle incoming webhook from SUMIT
     */
    public function handle(Request $request): array
    {
        try {
            // Log incoming webhook
            if (config('sumit-payment.logging.enabled')) {
                Log::info('SUMIT Webhook Received', [
                    'payload' => $request->all(),
                ]);
            }

            // Validate webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Invalid webhook signature');
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            // Parse webhook payload
            $payload = $this->parsePayload($request);
            $eventType = $payload['event_type'];

            // Dispatch generic webhook event
            Event::dispatch(new WebhookReceived($eventType, $payload));

            // Handle specific webhook types
            $this->handleWebhookEvent($eventType, $payload);

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Webhook processing failed',
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(Request $request): bool
    {
        // If signature verification is disabled or in testing mode
        if (!config('sumit-payment.webhooks.signature_verification') || $this->settings->testing_mode) {
            return true;
        }

        // Get signature from header
        $signature = $request->header('X-SUMIT-Signature');
        
        if (!$signature) {
            // Allow if no signature required
            return true;
        }

        // Validate signature using API key
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->settings->api_key);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook payload
     */
    public function parsePayload(Request $request): array
    {
        $data = $request->all();

        // Normalize event type
        $eventType = $data['EventType'] ?? $data['event_type'] ?? 'unknown';

        // Normalize transaction ID
        $transactionId = $data['TransactionID'] ?? $data['transaction_id'] ?? null;

        return [
            'event_type' => $eventType,
            'transaction_id' => $transactionId,
            'amount' => $data['Amount'] ?? $data['amount'] ?? 0,
            'currency' => $data['Currency'] ?? $data['currency'] ?? 'ILS',
            'document_id' => $data['DocumentID'] ?? $data['document_id'] ?? null,
            'customer_id' => $data['CustomerID'] ?? $data['customer_id'] ?? null,
            'error_message' => $data['ErrorMessage'] ?? $data['error_message'] ?? null,
            'refund_amount' => $data['RefundAmount'] ?? $data['refund_amount'] ?? null,
            'refund_document_id' => $data['RefundDocumentID'] ?? $data['refund_document_id'] ?? null,
            'raw_data' => $data,
        ];
    }

    /**
     * Handle specific webhook event
     */
    protected function handleWebhookEvent(string $eventType, array $payload): void
    {
        match($eventType) {
            'payment.completed', 'PaymentCompleted' => $this->handlePaymentCompleted($payload),
            'payment.failed', 'PaymentFailed' => $this->handlePaymentFailed($payload),
            'payment.refunded', 'PaymentRefunded' => $this->handlePaymentRefunded($payload),
            'payment.authorized', 'PaymentAuthorized' => $this->handlePaymentAuthorized($payload),
            default => $this->handleGenericWebhook($payload),
        };
    }

    /**
     * Handle payment completed webhook
     */
    protected function handlePaymentCompleted(array $payload): void
    {
        if (!$payload['transaction_id']) {
            return;
        }

        Event::dispatch(new PaymentCompleted(
            transactionId: $payload['transaction_id'],
            amount: $payload['amount'],
            currency: $payload['currency'],
            documentId: $payload['document_id'],
            customerId: $payload['customer_id'],
            metadata: [
                'webhook_received_at' => now()->toDateTimeString(),
                'webhook_data' => $payload['raw_data'],
            ]
        ));
    }

    /**
     * Handle payment failed webhook
     */
    protected function handlePaymentFailed(array $payload): void
    {
        if (!$payload['transaction_id']) {
            return;
        }

        Event::dispatch(new PaymentFailed(
            transactionId: $payload['transaction_id'],
            errorMessage: $payload['error_message'] ?? 'Payment failed',
            amount: $payload['amount'],
            metadata: [
                'webhook_received_at' => now()->toDateTimeString(),
                'webhook_data' => $payload['raw_data'],
            ]
        ));
    }

    /**
     * Handle payment refunded webhook
     */
    protected function handlePaymentRefunded(array $payload): void
    {
        if (!$payload['transaction_id']) {
            return;
        }

        Event::dispatch(new PaymentRefunded(
            transactionId: $payload['transaction_id'],
            refundAmount: $payload['refund_amount'] ?? 0,
            isPartial: ($payload['refund_amount'] ?? 0) < $payload['amount'],
            refundDocumentId: $payload['refund_document_id'],
            metadata: [
                'webhook_received_at' => now()->toDateTimeString(),
                'webhook_data' => $payload['raw_data'],
            ]
        ));
    }

    /**
     * Handle payment authorized webhook
     */
    protected function handlePaymentAuthorized(array $payload): void
    {
        if (!$payload['transaction_id']) {
            return;
        }

        // Fire status changed event
        Event::dispatch(new PaymentStatusChanged(
            transactionId: $payload['transaction_id'],
            newStatus: 'authorized',
            oldStatus: 'pending',
            metadata: [
                'webhook_received_at' => now()->toDateTimeString(),
                'webhook_data' => $payload['raw_data'],
            ]
        ));
    }

    /**
     * Handle generic webhook
     */
    protected function handleGenericWebhook(array $payload): void
    {
        // Just log for debugging
        if (config('sumit-payment.logging.enabled')) {
            Log::info('Generic webhook handled', ['payload' => $payload]);
        }
    }
}
