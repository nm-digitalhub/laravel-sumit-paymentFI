<?php

namespace Sumit\LaravelPayment\Listeners\ModelListeners;

use Sumit\LaravelPayment\Events\TokenCreated;
use Sumit\LaravelPayment\Models\PaymentToken;
use Illuminate\Support\Facades\Log;

/**
 * Store Token in Database Listener
 * 
 * Optional listener that stores payment token in the package's PaymentToken model.
 * Users can choose to use this listener or create their own.
 */
class StoreTokenInDatabase
{
    /**
     * Handle the event.
     */
    public function handle(TokenCreated $event): void
    {
        // Only proceed if PaymentToken model is enabled
        if (!$this->isModelEnabled()) {
            return;
        }

        try {
            $tokenData = $event->tokenData;

            PaymentToken::create([
                'user_id' => $event->userId,
                'token' => $tokenData->token,
                'last_four_digits' => $tokenData->lastFourDigits,
                'expiry_month' => $tokenData->expiryMonth,
                'expiry_year' => $tokenData->expiryYear,
                'card_type' => $tokenData->cardType,
                'cardholder_name' => $tokenData->cardholderName,
                'is_default' => $tokenData->isDefault,
                'is_active' => true,
                'metadata' => $tokenData->metadata,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store token in database', [
                'error' => $e->getMessage(),
                'user_id' => $event->userId,
            ]);
        }
    }

    /**
     * Check if PaymentToken model is enabled
     */
    protected function isModelEnabled(): bool
    {
        return config('sumit-payment.models.token') !== null;
    }
}
