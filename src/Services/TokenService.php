<?php

namespace Sumit\LaravelPayment\Services;

use Sumit\LaravelPayment\Models\PaymentToken;
use Illuminate\Support\Collection;

class TokenService
{
    /**
     * Create a new payment token.
     */
    public function createToken(
        int $userId,
        string $token,
        string $lastFour,
        string $expiryMonth,
        string $expiryYear,
        ?string $cardType = null,
        ?string $cardholderName = null,
        bool $isDefault = false
    ): PaymentToken {
        // If this is set as default, unset other defaults
        if ($isDefault) {
            $this->unsetDefaultTokens($userId);
        }

        return PaymentToken::create([
            'user_id' => $userId,
            'token' => $token,
            'card_type' => $cardType,
            'last_four' => $lastFour,
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
            'cardholder_name' => $cardholderName,
            'is_default' => $isDefault,
            'expires_at' => $this->calculateExpiryDate($expiryMonth, $expiryYear),
        ]);
    }

    /**
     * Get all tokens for a user.
     */
    public function getUserTokens(int $userId, bool $activeOnly = true): Collection
    {
        $query = PaymentToken::where('user_id', $userId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get default token for a user.
     */
    public function getDefaultToken(int $userId): ?PaymentToken
    {
        return PaymentToken::where('user_id', $userId)
            ->where('is_default', true)
            ->active()
            ->first();
    }

    /**
     * Get a specific token.
     */
    public function getToken(int $tokenId, ?int $userId = null): ?PaymentToken
    {
        $query = PaymentToken::where('id', $tokenId);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->first();
    }

    /**
     * Set a token as default.
     */
    public function setAsDefault(int $tokenId, int $userId): bool
    {
        $token = $this->getToken($tokenId, $userId);

        if (!$token) {
            return false;
        }

        // Unset other defaults
        $this->unsetDefaultTokens($userId);

        // Set this one as default
        $token->is_default = true;
        $token->save();

        return true;
    }

    /**
     * Delete a token.
     */
    public function deleteToken(int $tokenId, int $userId): bool
    {
        $token = $this->getToken($tokenId, $userId);

        if (!$token) {
            return false;
        }

        // If this was the default token, set another one as default
        if ($token->is_default) {
            $token->delete();
            $this->setFirstTokenAsDefault($userId);
        } else {
            $token->delete();
        }

        return true;
    }

    /**
     * Unset all default tokens for a user.
     */
    protected function unsetDefaultTokens(int $userId): void
    {
        PaymentToken::where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Set the first available token as default.
     */
    protected function setFirstTokenAsDefault(int $userId): void
    {
        $firstToken = PaymentToken::where('user_id', $userId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($firstToken) {
            $firstToken->is_default = true;
            $firstToken->save();
        }
    }

    /**
     * Calculate expiry date from month and year.
     */
    protected function calculateExpiryDate(string $month, string $year): \DateTime
    {
        // If year is 2 digits, convert to 4
        if (strlen($year) === 2) {
            $year = '20' . $year;
        }

        // Last day of the expiry month
        return new \DateTime($year . '-' . $month . '-01 23:59:59');
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        return PaymentToken::where('expires_at', '<', now())
            ->delete();
    }
}
