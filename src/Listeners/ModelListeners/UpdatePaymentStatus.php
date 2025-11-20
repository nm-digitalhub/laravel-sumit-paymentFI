<?php

namespace Sumit\LaravelPayment\Listeners\ModelListeners;

use Sumit\LaravelPayment\Events\PaymentCompleted;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Update Payment Status Listener
 * 
 * Optional listener that updates payment status in the package's Transaction model.
 * Users can choose to use this listener or create their own.
 */
class UpdatePaymentStatus
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        // Only proceed if Transaction model is enabled
        if (!$this->isModelEnabled()) {
            return;
        }

        try {
            $transaction = Transaction::where('transaction_id', $event->transactionId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'completed',
                    'document_id' => $event->documentId,
                    'authorization_number' => $event->authorizationNumber,
                    'customer_id' => $event->customerId,
                    'processed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], $event->metadata),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update payment status', [
                'error' => $e->getMessage(),
                'transaction_id' => $event->transactionId,
            ]);
        }
    }

    /**
     * Check if Transaction model is enabled
     */
    protected function isModelEnabled(): bool
    {
        return config('sumit-payment.models.transaction') !== null;
    }
}
