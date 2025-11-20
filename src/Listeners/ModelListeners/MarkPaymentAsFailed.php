<?php

namespace Sumit\LaravelPayment\Listeners\ModelListeners;

use Sumit\LaravelPayment\Events\PaymentFailed;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Mark Payment as Failed Listener
 * 
 * Optional listener that marks payment as failed in the package's Transaction model.
 * Users can choose to use this listener or create their own.
 */
class MarkPaymentAsFailed
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        // Only proceed if Transaction model is enabled
        if (!$this->isModelEnabled()) {
            return;
        }

        try {
            $transaction = Transaction::where('transaction_id', $event->transactionId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $event->errorMessage,
                    'processed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], $event->metadata),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark payment as failed', [
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
