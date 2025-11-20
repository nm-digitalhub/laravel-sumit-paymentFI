<?php

namespace Sumit\LaravelPayment\Listeners\ModelListeners;

use Sumit\LaravelPayment\Events\PaymentCreated;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Store Payment in Database Listener
 * 
 * Optional listener that stores payment data in the package's Transaction model.
 * Users can choose to use this listener or create their own.
 */
class StorePaymentInDatabase
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCreated $event): void
    {
        // Only proceed if Transaction model is enabled
        if (!$this->isModelEnabled()) {
            return;
        }

        try {
            $request = $event->request;
            $response = $event->response;

            Transaction::create([
                'transaction_id' => $response->transactionId,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'status' => $response->status,
                'description' => $request->description,
                'is_donation' => $request->isDonation,
                'is_subscription' => $request->isSubscription,
                'payments_count' => $request->paymentsCount,
                'metadata' => array_merge($request->metadata, [
                    'payment_url' => $response->paymentUrl,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store payment in database', [
                'error' => $e->getMessage(),
                'transaction_id' => $event->response->transactionId,
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
