<?php

namespace NmDigitalHub\LaravelSumitPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelSumitPayment\Services\PaymentService;
use Illuminate\Support\Facades\Validator;

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
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'items' => 'required|array',
            'customer' => 'required|array',
            'customer.name' => 'required|string',
            'customer.email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $paymentData = [
            'order_id' => $request->input('order_id'),
            'user_id' => auth()->id(),
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency'),
            'items' => $request->input('items'),
            'customer' => $request->input('customer'),
            'vat_rate' => $request->input('vat_rate'),
            'payments_count' => $request->input('payments_count', 1),
            'description' => $request->input('description'),
            'is_subscription' => $request->input('is_subscription', false),
            'save_token' => $request->input('save_token', false),
            'payment_token_id' => $request->input('payment_token_id'),
            'payment_method' => $request->input('payment_method'),
            'single_use_token' => $request->input('single_use_token'),
            'redirect_url' => $request->input('redirect_url'),
            'metadata' => $request->input('metadata'),
        ];

        $result = $this->paymentService->processPayment($paymentData);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Process a refund.
     */
    public function processRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->paymentService->processRefund(
            $request->input('order_id'),
            $request->input('amount'),
            $request->input('description')
        );

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }
}
