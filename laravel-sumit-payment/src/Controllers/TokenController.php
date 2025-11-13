<?php

namespace Sumit\LaravelPayment\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sumit\LaravelPayment\Services\TokenService;
use Sumit\LaravelPayment\Services\PaymentService;

class TokenController extends Controller
{
    protected TokenService $tokenService;
    protected PaymentService $paymentService;

    public function __construct(TokenService $tokenService, PaymentService $paymentService)
    {
        $this->tokenService = $tokenService;
        $this->paymentService = $paymentService;
    }

    /**
     * Get all tokens for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $tokens = $this->tokenService->getUserTokens($request->user()->id);

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new token (tokenize card).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'card_number' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string',
            'cvv' => 'sometimes|string',
            'cardholder_name' => 'sometimes|string',
            'is_default' => 'sometimes|boolean',
        ]);

        $result = $this->paymentService->tokenizeCard($validated, $request->user()->id);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Set a token as default.
     */
    public function setDefault(Request $request, int $tokenId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $success = $this->tokenService->setAsDefault($tokenId, $request->user()->id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token set as default',
        ]);
    }

    /**
     * Delete a token.
     */
    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $success = $this->tokenService->deleteToken($tokenId, $request->user()->id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token deleted successfully',
        ]);
    }
}
