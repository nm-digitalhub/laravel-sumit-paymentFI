<?php

namespace NmDigitalHub\LaravelOfficeGuy\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelOfficeGuy\Services\PaymentService;
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
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'user_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'customer' => 'required|array',
            'customer.name' => 'required|string',
            'customer.email' => 'required|email',
            'customer.phone' => 'nullable|string',
            'single_use_token' => 'nullable|string',
            'token_id' => 'nullable|integer',
            'save_token' => 'nullable|boolean',
            'payments_count' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $paymentData = $request->all();
        
        // Add authenticated user ID if not provided
        if (!isset($paymentData['user_id']) && auth()->check()) {
            $paymentData['user_id'] = auth()->id();
        }

        $result = $this->paymentService->processPayment($paymentData);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get payment details.
     */
    public function show($paymentId)
    {
        $payment = \NmDigitalHub\LaravelOfficeGuy\Models\Payment::find($paymentId);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found',
            ], 404);
        }

        // Check authorization
        if (auth()->check() && $payment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
        ]);
    }

    /**
     * Refund a payment.
     */
    public function refund(Request $request, $paymentId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $payment = \NmDigitalHub\LaravelOfficeGuy\Models\Payment::find($paymentId);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found',
            ], 404);
        }

        $amount = $request->input('amount');
        $result = $this->paymentService->refundPayment($payment, $amount);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * List user payments.
     */
    public function index(Request $request)
    {
        $userId = $request->input('user_id', auth()->id());

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => 'User ID required',
            ], 400);
        }

        // Check authorization
        if (auth()->check() && $userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $payments = \NmDigitalHub\LaravelOfficeGuy\Models\Payment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'payments' => $payments,
        ]);
    }
}
