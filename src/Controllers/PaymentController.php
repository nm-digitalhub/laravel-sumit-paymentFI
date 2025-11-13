<?php

namespace Sumit\LaravelPayment\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sumit\LaravelPayment\Services\PaymentService;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process a payment.
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'order_id' => 'sometimes|string',
            'description' => 'sometimes|string',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'sometimes|string',
            'card_number' => 'required_without:token_id|string',
            'expiry_month' => 'required_without:token_id|string|size:2',
            'expiry_year' => 'required_without:token_id|string',
            'cvv' => 'sometimes|string',
            'token_id' => 'sometimes|integer|exists:sumit_payment_tokens,id',
            'payments_count' => 'sometimes|integer|min:1|max:12',
            'save_card' => 'sometimes|boolean',
        ]);

        // Add user ID if authenticated
        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        // Process payment with token or direct
        if (isset($validated['token_id'])) {
            $result = $this->paymentService->processPaymentWithToken($validated, $validated['token_id']);
        } else {
            $result = $this->paymentService->processPayment($validated);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Handle redirect callback from payment gateway.
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $transactionId = $request->get('transaction');

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID is required',
            ], 400);
        }

        $result = $this->paymentService->processRedirectCallback($transactionId, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get transaction details.
     */
    public function getTransaction(Request $request, string $transactionId): JsonResponse
    {
        $transaction = \Sumit\LaravelPayment\Models\Transaction::find($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // Check if user has access to this transaction
        if ($request->user() && $transaction->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }
}
