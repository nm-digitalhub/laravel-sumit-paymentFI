<?php

namespace Sumit\LaravelPayment\Listeners\ModelListeners;

use Sumit\LaravelPayment\Events\PaymentRefunded;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Record Refund Listener
 * 
 * Optional listener that records refund data in the package's Transaction model.
 * Users can choose to use this listener or create their own.
 */
class RecordRefund
{
    /**
     * Handle the event.
     */
    public function handle(PaymentRefunded $event): void
    {
        // Only proceed if Transaction model is enabled
        if (!$this->isModelEnabled()) {
            return;
        }

        try {
            $transaction = Transaction::where('transaction_id', $event->transactionId)->first();

            if ($transaction) {
                $transaction->update([
                    'refund_amount' => ($transaction->refund_amount ?? 0) + $event->refundAmount,
                    'refund_status' => $event->isPartial ? 'partial' : 'full',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'refund_document_id' => $event->refundDocumentId,
                        'refund_reason' => $event->reason,
                        'refunded_at' => now()->toDateTimeString(),
                    ], $event->metadata),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record refund', [
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
