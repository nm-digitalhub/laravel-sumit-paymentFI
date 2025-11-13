<?php

namespace NmDigitalHub\LaravelOfficeGuy\Services;

use NmDigitalHub\LaravelOfficeGuy\Models\Payment;
use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;

class SubscriptionService
{
    protected PaymentService $paymentService;
    protected TokenService $tokenService;

    public function __construct(PaymentService $paymentService, TokenService $tokenService)
    {
        $this->paymentService = $paymentService;
        $this->tokenService = $tokenService;
    }

    /**
     * Process subscription payment.
     */
    public function processSubscriptionPayment(array $subscriptionData): array
    {
        // Get or create token for subscription
        $token = null;
        if (isset($subscriptionData['token_id'])) {
            $token = PaymentToken::find($subscriptionData['token_id']);
        } elseif (isset($subscriptionData['user_id'])) {
            $token = $this->tokenService->getUserDefaultToken($subscriptionData['user_id']);
        }

        if (!$token) {
            return [
                'success' => false,
                'error' => 'No payment token found for subscription',
            ];
        }

        // Prepare payment data
        $paymentData = array_merge($subscriptionData, [
            'token_id' => $token->id,
            'is_subscription' => true,
            'auto_capture' => true,
        ]);

        // Process the payment
        return $this->paymentService->processPayment($paymentData);
    }

    /**
     * Create subscription with initial payment.
     */
    public function createSubscription(array $subscriptionData): array
    {
        try {
            // First, create a token if needed
            $token = null;
            if (isset($subscriptionData['card_data']) || isset($subscriptionData['single_use_token'])) {
                $tokenResult = $this->tokenService->createToken([
                    'user_id' => $subscriptionData['user_id'],
                    'card_data' => $subscriptionData['card_data'] ?? null,
                    'single_use_token' => $subscriptionData['single_use_token'] ?? null,
                    'set_as_default' => true,
                ]);

                if (!$tokenResult['success']) {
                    return $tokenResult;
                }

                $token = $tokenResult['token'];
            } elseif (isset($subscriptionData['token_id'])) {
                $token = PaymentToken::find($subscriptionData['token_id']);
            }

            if (!$token) {
                return [
                    'success' => false,
                    'error' => 'Failed to create or find payment token',
                ];
            }

            // Process initial payment if amount > 0
            if (isset($subscriptionData['amount']) && $subscriptionData['amount'] > 0) {
                $paymentResult = $this->processSubscriptionPayment(array_merge(
                    $subscriptionData,
                    ['token_id' => $token->id]
                ));

                if (!$paymentResult['success']) {
                    return $paymentResult;
                }

                return [
                    'success' => true,
                    'subscription' => [
                        'token' => $token,
                        'initial_payment' => $paymentResult['payment'],
                    ],
                ];
            }

            // No initial payment required
            return [
                'success' => true,
                'subscription' => [
                    'token' => $token,
                    'initial_payment' => null,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update subscription payment method.
     */
    public function updateSubscriptionPaymentMethod(int $subscriptionId, array $tokenData): array
    {
        // Create new token
        $tokenResult = $this->tokenService->createToken($tokenData);

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        // Here you would update your subscription record with the new token
        // This depends on your application's subscription management system
        
        return [
            'success' => true,
            'token' => $tokenResult['token'],
        ];
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(int $subscriptionId): array
    {
        // Implement subscription cancellation logic
        // This depends on your application's subscription management system
        
        return [
            'success' => true,
            'message' => 'Subscription cancelled',
        ];
    }

    /**
     * Get subscription payments.
     */
    public function getSubscriptionPayments(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::where('user_id', $userId)
            ->subscription()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Validate subscription data.
     */
    public function validateSubscriptionData(array $subscriptionData): array
    {
        $errors = [];

        if (empty($subscriptionData['user_id'])) {
            $errors[] = 'User ID is required';
        }

        if (empty($subscriptionData['amount']) && !isset($subscriptionData['amount'])) {
            $errors[] = 'Amount is required';
        }

        if (isset($subscriptionData['amount']) && $subscriptionData['amount'] < 0) {
            $errors[] = 'Amount must be positive';
        }

        return $errors;
    }
}
