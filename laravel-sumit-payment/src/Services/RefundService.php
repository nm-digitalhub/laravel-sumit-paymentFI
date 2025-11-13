<?php

namespace Sumit\LaravelPayment\Services;

use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Events\RefundProcessed;
use Illuminate\Support\Facades\Event;

class RefundService
{
    protected ApiService $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Process a refund for a transaction.
     */
    public function processRefund(Transaction $transaction, float $amount = null, string $reason = ''): array
    {
        // Use full transaction amount if no specific amount provided
        $refundAmount = $amount ?? $transaction->amount;

        // Validate refund amount
        if ($refundAmount > $transaction->amount) {
            return [
                'success' => false,
                'message' => 'Refund amount cannot exceed transaction amount',
            ];
        }

        if ($refundAmount <= 0) {
            return [
                'success' => false,
                'message' => 'Refund amount must be greater than zero',
            ];
        }

        // Check if transaction can be refunded
        if (!in_array($transaction->status, ['completed', 'authorized'])) {
            return [
                'success' => false,
                'message' => 'Transaction cannot be refunded. Status: ' . $transaction->status,
            ];
        }

        try {
            // Build refund request
            $request = [
                'CompanyID' => config('sumit-payment.company_id'),
                'APIKey' => config('sumit-payment.api_key'),
                'DocumentID' => $transaction->document_id,
                'Amount' => $refundAmount,
                'Reason' => $reason ?: 'Customer requested refund',
            ];

            // Call SUMIT refund API
            $response = $this->apiService->post($request, 'CreateCreditInvoice');

            if (!$response || !isset($response['ResponseID'])) {
                throw new \Exception('Invalid response from refund API');
            }

            // Check response status
            if ($response['ResponseID'] != 0) {
                throw new \Exception($response['ResponseText'] ?? 'Refund failed');
            }

            // Update transaction
            $transaction->update([
                'refund_amount' => ($transaction->refund_amount ?? 0) + $refundAmount,
                'refund_status' => $refundAmount >= $transaction->amount ? 'full' : 'partial',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_document_id' => $response['DocumentID'] ?? null,
                    'refund_date' => now()->toDateTimeString(),
                    'refund_reason' => $reason,
                ]),
            ]);

            // Dispatch refund event
            Event::dispatch(new RefundProcessed($transaction, $refundAmount, $reason));

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'transaction' => $transaction,
                'refund_amount' => $refundAmount,
                'refund_document_id' => $response['DocumentID'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction' => $transaction,
            ];
        }
    }

    /**
     * Get refund details for a transaction.
     */
    public function getRefundDetails(Transaction $transaction): array
    {
        return [
            'transaction_id' => $transaction->id,
            'original_amount' => $transaction->amount,
            'refunded_amount' => $transaction->refund_amount ?? 0,
            'remaining_refundable' => $transaction->amount - ($transaction->refund_amount ?? 0),
            'refund_status' => $transaction->refund_status ?? 'none',
            'can_refund' => in_array($transaction->status, ['completed', 'authorized']) && 
                           ($transaction->refund_amount ?? 0) < $transaction->amount,
        ];
    }

    /**
     * Check if transaction can be refunded.
     */
    public function canRefund(Transaction $transaction): bool
    {
        return in_array($transaction->status, ['completed', 'authorized']) && 
               ($transaction->refund_amount ?? 0) < $transaction->amount;
    }
}
