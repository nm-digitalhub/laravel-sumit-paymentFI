<?php

namespace NmDigitalHub\LaravelSumitPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelSumitPayment\Services\PaymentService;
use NmDigitalHub\LaravelSumitPayment\Models\Transaction;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle payment callback from SUMIT.
     */
    public function handleCallback(Request $request)
    {
        // Log incoming webhook
        Log::channel(config('sumit-payment.log_channel'))
            ->info('SUMIT Webhook Received', $request->all());

        $orderId = $request->input('OG-OrderID');
        $paymentId = $request->input('OG-PaymentID');
        $documentId = $request->input('OG-DocumentID');

        if (!$orderId || !$paymentId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        // Fetch payment details from SUMIT API
        $apiService = app(\NmDigitalHub\LaravelSumitPayment\Services\SumitApiService::class);
        
        $response = $apiService->post('/billing/payments/get/', [
            'PaymentID' => $paymentId,
        ], false);

        if (!$response || !isset($response['Data']['Payment'])) {
            return response()->json(['error' => 'Failed to fetch payment details'], 500);
        }

        $payment = $response['Data']['Payment'];

        if ($payment['ValidPayment'] !== true) {
            // Payment failed
            Transaction::where('order_id', $orderId)->update([
                'status' => 'failed',
                'status_description' => $payment['StatusDescription'] ?? 'Payment failed',
            ]);

            return response()->json(['status' => 'failed']);
        }

        // Payment succeeded
        $paymentMethod = $payment['PaymentMethod'];
        
        Transaction::updateOrCreate(
            ['order_id' => $orderId],
            [
                'payment_id' => $payment['ID'],
                'auth_number' => $payment['AuthNumber'],
                'amount' => $payment['Amount'],
                'status' => 'completed',
                'valid_payment' => true,
                'card_last_four' => $paymentMethod['CreditCard_LastDigits'] ?? null,
                'card_expiry_month' => $paymentMethod['CreditCard_ExpirationMonth'] ?? null,
                'card_expiry_year' => $paymentMethod['CreditCard_ExpirationYear'] ?? null,
                'document_id' => $documentId,
                'customer_id' => $payment['CustomerID'] ?? null,
            ]
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Bit payment IPN.
     */
    public function handleBitIpn(Request $request)
    {
        Log::channel(config('sumit-payment.log_channel'))
            ->info('SUMIT Bit IPN Received', $request->all());

        $orderId = $request->input('orderid');
        $orderKey = $request->input('orderkey');

        // Verify order key if needed
        // Process the Bit payment similar to handleCallback

        return response()->json(['status' => 'received']);
    }
}
