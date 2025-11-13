<?php

namespace NmDigitalHub\LaravelOfficeGuy\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelOfficeGuy\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle payment callback/webhook.
     */
    public function handle(Request $request)
    {
        Log::info('[OfficeGuy] Webhook received', [
            'data' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            // Extract payment data from callback
            $orderId = $request->input('OG-OrderID');
            $transactionId = $request->input('TransactionID');
            $status = $request->input('Status');
            $success = $request->input('Success');

            if (!$orderId) {
                Log::warning('[OfficeGuy] Webhook missing order ID');
                return response()->json([
                    'success' => false,
                    'error' => 'Missing order ID',
                ], 400);
            }

            // Find the payment by order ID or transaction ID
            $payment = \NmDigitalHub\LaravelOfficeGuy\Models\Payment::where('order_id', $orderId)
                ->orWhere('transaction_id', $transactionId)
                ->first();

            if (!$payment) {
                Log::warning('[OfficeGuy] Payment not found for webhook', [
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Payment not found',
                ], 404);
            }

            // Update payment based on callback data
            if ($success === true || $success === 'true' || $status === 0) {
                $payment->update([
                    'status' => 'success',
                    'transaction_id' => $transactionId,
                    'response_data' => $request->all(),
                    'captured_at' => now(),
                ]);

                Log::info('[OfficeGuy] Payment marked as successful', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                ]);
            } else {
                $errorMessage = $request->input('ErrorMessage', 'Payment failed');
                $payment->markAsFailed($errorMessage);

                Log::warning('[OfficeGuy] Payment marked as failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                    'error' => $errorMessage,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ]);
        } catch (\Exception $e) {
            Log::error('[OfficeGuy] Webhook processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle redirect callback (for redirect payment flow).
     */
    public function redirect(Request $request)
    {
        $orderId = $request->input('OG-OrderID');
        $success = $request->input('Success');
        $errorMessage = $request->input('ErrorMessage');

        Log::info('[OfficeGuy] Redirect callback received', [
            'order_id' => $orderId,
            'success' => $success,
        ]);

        // Find payment and update
        if ($orderId) {
            $payment = \NmDigitalHub\LaravelOfficeGuy\Models\Payment::where('order_id', $orderId)->first();

            if ($payment) {
                if ($success === true || $success === 'true') {
                    $payment->markAsSuccessful();
                } else {
                    $payment->markAsFailed($errorMessage);
                }
            }
        }

        // Redirect to success/failure page
        $redirectUrl = $success ? 
            config('officeguy.routes.success_url', '/payment/success') : 
            config('officeguy.routes.failure_url', '/payment/failure');

        return redirect($redirectUrl)->with([
            'order_id' => $orderId,
            'success' => $success,
            'error' => $errorMessage,
        ]);
    }
}
