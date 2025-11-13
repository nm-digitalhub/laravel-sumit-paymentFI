<?php

namespace NmDigitalHub\LaravelOfficeGuy\Services;

use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;
use NmDigitalHub\LaravelOfficeGuy\Events\TokenCreated;

class TokenService
{
    protected OfficeGuyApiService $apiService;

    public function __construct(OfficeGuyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Create a payment token.
     */
    public function createToken(array $tokenData): array
    {
        try {
            // Build token request
            $request = $this->buildTokenRequest($tokenData);

            // Send to API
            $response = $this->apiService->post(
                $request,
                '/creditguy/gateway/transaction/',
                false
            );

            if (!$response) {
                return [
                    'success' => false,
                    'error' => 'No response from payment gateway',
                ];
            }

            // Process response
            if (isset($response['Status']) && $response['Status'] == 0 && 
                isset($response['Data']['Success']) && $response['Data']['Success'] == true) {
                
                $token = $this->createTokenFromResponse($response, $tokenData['user_id']);

                if ($token) {
                    // Set as default if requested
                    if (isset($tokenData['set_as_default']) && $tokenData['set_as_default']) {
                        $token->setAsDefault();
                    }

                    // Fire event
                    event(new TokenCreated($token));

                    return [
                        'success' => true,
                        'token' => $token,
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Failed to save token',
                ];
            } else {
                $error = $response['UserErrorMessage'] ?? 
                        ($response['Data']['ResultDescription'] ?? 'Token creation failed');

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }
        } catch (\Exception $e) {
            $this->apiService->writeToLog('Token creation error: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build token request for API.
     */
    protected function buildTokenRequest(array $tokenData): array
    {
        $request = [
            'ParamJ' => config('officeguy.tokens.token_param', '5'),
            'Amount' => 1,
            'Credentials' => $this->apiService->getCredentials(),
        ];

        // Add card data based on method
        if (isset($tokenData['single_use_token'])) {
            $request['SingleUseToken'] = $tokenData['single_use_token'];
        } elseif (isset($tokenData['card_data'])) {
            $cardData = $tokenData['card_data'];
            $request['CardNumber'] = $cardData['number'];
            $request['CVV'] = $cardData['cvv'];
            $request['CitizenID'] = $cardData['citizen_id'] ?? '';
            $request['ExpirationMonth'] = str_pad($cardData['expiry_month'], 2, '0', STR_PAD_LEFT);
            $request['ExpirationYear'] = $cardData['expiry_year'];
        }

        return $request;
    }

    /**
     * Create payment token from API response.
     */
    protected function createTokenFromResponse(array $response, $userId): ?PaymentToken
    {
        if (!isset($response['Data']['CardToken'])) {
            return null;
        }

        $data = $response['Data'];

        return PaymentToken::create([
            'user_id' => $userId,
            'token' => $data['CardToken'],
            'card_type' => 'card',
            'last_four' => substr($data['CardPattern'] ?? '', -4),
            'expiry_month' => $data['ExpirationMonth'] ?? '',
            'expiry_year' => $data['ExpirationYear'] ?? '',
            'card_pattern' => $data['CardPattern'] ?? null,
            'citizen_id' => $data['CitizenID'] ?? null,
            'brand' => $data['Brand'] ?? null,
        ]);
    }

    /**
     * Get user's tokens.
     */
    public function getUserTokens(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentToken::forUser($userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's default token.
     */
    public function getUserDefaultToken(int $userId): ?PaymentToken
    {
        return PaymentToken::forUser($userId)
            ->default()
            ->first();
    }

    /**
     * Delete a token.
     */
    public function deleteToken(int $tokenId, int $userId): bool
    {
        $token = PaymentToken::where('id', $tokenId)
            ->where('user_id', $userId)
            ->first();

        if (!$token) {
            return false;
        }

        return $token->delete();
    }

    /**
     * Set token as default.
     */
    public function setTokenAsDefault(int $tokenId, int $userId): bool
    {
        $token = PaymentToken::where('id', $tokenId)
            ->where('user_id', $userId)
            ->first();

        if (!$token) {
            return false;
        }

        $token->setAsDefault();
        return true;
    }

    /**
     * Validate card data.
     */
    public function validateCardData(array $cardData): array
    {
        $errors = [];

        // Validate card number
        if (empty($cardData['number']) || !ctype_digit(str_replace(' ', '', $cardData['number']))) {
            $errors[] = 'Card number is invalid';
        }

        // Validate CVV
        if (empty($cardData['cvv']) || !ctype_digit($cardData['cvv']) || 
            strlen($cardData['cvv']) < 3 || strlen($cardData['cvv']) > 4) {
            $errors[] = 'CVV is invalid';
        }

        // Validate expiry month
        if (empty($cardData['expiry_month']) || !ctype_digit($cardData['expiry_month']) || 
            $cardData['expiry_month'] < 1 || $cardData['expiry_month'] > 12) {
            $errors[] = 'Expiry month is invalid';
        }

        // Validate expiry year
        $currentYear = date('Y');
        if (empty($cardData['expiry_year']) || !ctype_digit($cardData['expiry_year']) || 
            $cardData['expiry_year'] < $currentYear || $cardData['expiry_year'] > $currentYear + 20) {
            $errors[] = 'Expiry year is invalid';
        }

        return $errors;
    }
}
