<?php

namespace NmDigitalHub\LaravelOfficeGuy\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelOfficeGuy\Services\TokenService;
use Illuminate\Support\Facades\Validator;

class TokenController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Create a new payment token.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'single_use_token' => 'required_without:card_data|string',
            'card_data' => 'required_without:single_use_token|array',
            'card_data.number' => 'required_with:card_data|string',
            'card_data.cvv' => 'required_with:card_data|string',
            'card_data.expiry_month' => 'required_with:card_data|integer|min:1|max:12',
            'card_data.expiry_year' => 'required_with:card_data|integer',
            'card_data.citizen_id' => 'nullable|string',
            'set_as_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check authorization
        if (auth()->check() && $request->input('user_id') !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        // Validate card data if provided
        if ($request->has('card_data')) {
            $cardErrors = $this->tokenService->validateCardData($request->input('card_data'));
            if (!empty($cardErrors)) {
                return response()->json([
                    'success' => false,
                    'errors' => $cardErrors,
                ], 422);
            }
        }

        $result = $this->tokenService->createToken($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * List user tokens.
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

        $tokens = $this->tokenService->getUserTokens($userId);

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Delete a token.
     */
    public function destroy(Request $request, $tokenId)
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

        $result = $this->tokenService->deleteToken($tokenId, $userId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Token deleted' : 'Token not found',
        ], $result ? 200 : 404);
    }

    /**
     * Set token as default.
     */
    public function setDefault(Request $request, $tokenId)
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

        $result = $this->tokenService->setTokenAsDefault($tokenId, $userId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Token set as default' : 'Token not found',
        ], $result ? 200 : 404);
    }
}
