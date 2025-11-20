<?php

namespace NmDigitalHub\LaravelSumitPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelSumitPayment\Services\TokenService;
use Illuminate\Support\Facades\Validator;

class TokenController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get user's payment tokens.
     */
    public function index()
    {
        $tokens = $this->tokenService->getUserTokens(auth()->id());

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new payment token.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required_if:pci_mode,yes|string',
            'cvv' => 'required_if:pci_mode,yes|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:4',
            'citizen_id' => 'nullable|string',
            'single_use_token' => 'required_if:pci_mode,no|string',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $cardData = [
            'card_number' => $request->input('card_number'),
            'cvv' => $request->input('cvv'),
            'expiry_month' => $request->input('expiry_month'),
            'expiry_year' => $request->input('expiry_year'),
            'citizen_id' => $request->input('citizen_id'),
            'single_use_token' => $request->input('single_use_token'),
            'is_default' => $request->input('is_default', false),
        ];

        $result = $this->tokenService->generateToken($cardData, auth()->id());

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Set a token as default.
     */
    public function setDefault($tokenId)
    {
        $success = $this->tokenService->setDefaultToken($tokenId, auth()->id());

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Token set as default',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Token not found',
        ], 404);
    }

    /**
     * Delete a payment token.
     */
    public function destroy($tokenId)
    {
        $success = $this->tokenService->deleteToken($tokenId, auth()->id());

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Token deleted',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Token not found',
        ], 404);
    }
}
