<?php

namespace NmDigitalHub\LaravelSumitPayment\Services;

use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;
use NmDigitalHub\LaravelSumitPayment\Events\TokenCreated;

class TokenService
{
    protected SumitApiService $api;

    /**
     * Create a new TokenService instance.
     */
    public function __construct(SumitApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Create a payment token.
     */
    public function createToken(array $tokenData): PaymentToken
    {
        $token = PaymentToken::create([
            'user_id' => $tokenData['user_id'],
            'token' => $tokenData['token'],
            'card_type' => $tokenData['card_type'] ?? 'card',
            'last_four' => $tokenData['last_four'],
            'expiry_month' => $tokenData['expiry_month'],
            'expiry_year' => $tokenData['expiry_year'],
            'citizen_id' => $tokenData['citizen_id'] ?? null,
            'is_default' => $tokenData['is_default'] ?? false,
        ]);

        // If this is set as default, unset other defaults
        if ($token->is_default) {
            PaymentToken::where('user_id', $token->user_id)
                ->where('id', '!=', $token->id)
                ->update(['is_default' => false]);
        }

        event(new TokenCreated($token, $tokenData['user_id']));

        return $token;
    }

    /**
     * Generate a token from card details.
     */
    public function generateToken(array $cardData, int $userId): ?array
    {
        $request = $this->buildTokenRequest($cardData);
        
        $response = $this->api->post('/creditguy/gateway/transaction/', $request, false);

        if ($response['Status'] == 0 && ($response['Data']['Success'] ?? false)) {
            $token = $this->createToken([
                'user_id' => $userId,
                'token' => $response['Data']['CardToken'],
                'last_four' => substr($response['Data']['CardPattern'], -4),
                'expiry_month' => $response['Data']['ExpirationMonth'],
                'expiry_year' => $response['Data']['ExpirationYear'],
                'citizen_id' => $response['Data']['CitizenID'] ?? null,
                'is_default' => $cardData['is_default'] ?? false,
            ]);

            return [
                'success' => true,
                'token' => $token,
            ];
        }

        return [
            'success' => false,
            'error' => $response['UserErrorMessage'] ?? 'Token generation failed',
        ];
    }

    /**
     * Build token request.
     */
    protected function buildTokenRequest(array $cardData): array
    {
        $request = [
            'ParamJ' => config('sumit-payment.token_param', 'J5'),
            'Amount' => 1,
        ];

        if (config('sumit-payment.pci_compliance') === 'yes') {
            $request['CardNumber'] = $cardData['card_number'];
            $request['CVV'] = $cardData['cvv'];
            $request['CitizenID'] = $cardData['citizen_id'] ?? '';
            $request['ExpirationMonth'] = str_pad($cardData['expiry_month'], 2, '0', STR_PAD_LEFT);
            $request['ExpirationYear'] = $cardData['expiry_year'];
        } else {
            $request['SingleUseToken'] = $cardData['single_use_token'];
        }

        return $request;
    }

    /**
     * Get user's default token.
     */
    public function getDefaultToken(int $userId): ?PaymentToken
    {
        return PaymentToken::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get all user tokens.
     */
    public function getUserTokens(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentToken::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Set token as default.
     */
    public function setDefaultToken(int $tokenId, int $userId): bool
    {
        $token = PaymentToken::where('id', $tokenId)
            ->where('user_id', $userId)
            ->first();

        if (!$token) {
            return false;
        }

        // Unset other defaults
        PaymentToken::where('user_id', $userId)
            ->update(['is_default' => false]);

        // Set this as default
        $token->is_default = true;
        $token->save();

        return true;
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
}
