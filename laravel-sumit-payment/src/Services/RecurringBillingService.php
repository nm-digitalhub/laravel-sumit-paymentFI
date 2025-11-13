<?php

namespace Sumit\LaravelPayment\Services;

use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Models\PaymentToken;
use Sumit\LaravelPayment\Events\SubscriptionCreated;
use Sumit\LaravelPayment\Events\SubscriptionCharged;
use Illuminate\Support\Facades\Event;

class RecurringBillingService
{
    protected PaymentService $paymentService;
    protected TokenService $tokenService;

    public function __construct(PaymentService $paymentService, TokenService $tokenService)
    {
        $this->paymentService = $paymentService;
        $this->tokenService = $tokenService;
    }

    /**
     * Create a recurring billing subscription.
     */
    public function createSubscription(array $subscriptionData): array
    {
        // Validate required fields
        $required = ['user_id', 'amount', 'frequency', 'token_id'];
        foreach ($required as $field) {
            if (empty($subscriptionData[$field])) {
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}",
                ];
            }
        }

        // Get payment token
        $token = $this->tokenService->getToken($subscriptionData['token_id'], $subscriptionData['user_id']);
        
        if (!$token || $token->isExpired()) {
            return [
                'success' => false,
                'message' => 'Invalid or expired payment token',
            ];
        }

        try {
            // Process initial payment if required
            $initialPayment = null;
            if ($subscriptionData['charge_immediately'] ?? true) {
                $paymentResult = $this->chargeSubscription($subscriptionData);
                
                if (!$paymentResult['success']) {
                    return $paymentResult;
                }
                
                $initialPayment = $paymentResult['transaction'];
            }

            // Create subscription record
            $subscription = Transaction::create([
                'user_id' => $subscriptionData['user_id'],
                'amount' => $subscriptionData['amount'],
                'currency' => $subscriptionData['currency'] ?? 'ILS',
                'type' => 'subscription',
                'status' => 'active',
                'is_subscription' => true,
                'payment_token_id' => $token->id,
                'metadata' => [
                    'frequency' => $subscriptionData['frequency'], // daily, weekly, monthly, yearly
                    'start_date' => $subscriptionData['start_date'] ?? now()->toDateString(),
                    'next_billing_date' => $this->calculateNextBillingDate($subscriptionData['frequency']),
                    'end_date' => $subscriptionData['end_date'] ?? null,
                    'initial_transaction_id' => $initialPayment?->id,
                    'billing_day' => $subscriptionData['billing_day'] ?? now()->day,
                    'description' => $subscriptionData['description'] ?? 'Recurring subscription',
                ],
            ]);

            Event::dispatch(new SubscriptionCreated($subscription, $token));

            return [
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription' => $subscription,
                'initial_transaction' => $initialPayment,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Charge a subscription.
     */
    public function chargeSubscription(array $subscriptionData): array
    {
        $paymentData = [
            'user_id' => $subscriptionData['user_id'],
            'amount' => $subscriptionData['amount'],
            'currency' => $subscriptionData['currency'] ?? 'ILS',
            'customer_name' => $subscriptionData['customer_name'] ?? '',
            'customer_email' => $subscriptionData['customer_email'] ?? '',
            'description' => $subscriptionData['description'] ?? 'Subscription payment',
            'is_subscription' => true,
        ];

        $result = $this->paymentService->processPaymentWithToken($paymentData, $subscriptionData['token_id']);

        if ($result['success']) {
            Event::dispatch(new SubscriptionCharged($result['transaction']));
        }

        return $result;
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Transaction $subscription): array
    {
        if (!$subscription->is_subscription) {
            return [
                'success' => false,
                'message' => 'Transaction is not a subscription',
            ];
        }

        $subscription->update([
            'status' => 'cancelled',
            'metadata' => array_merge($subscription->metadata ?? [], [
                'cancelled_at' => now()->toDateTimeString(),
            ]),
        ]);

        return [
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'subscription' => $subscription,
        ];
    }

    /**
     * Update subscription details.
     */
    public function updateSubscription(Transaction $subscription, array $updates): array
    {
        if (!$subscription->is_subscription) {
            return [
                'success' => false,
                'message' => 'Transaction is not a subscription',
            ];
        }

        $allowedUpdates = ['amount', 'frequency', 'billing_day', 'description', 'end_date'];
        $metadata = $subscription->metadata ?? [];

        foreach ($updates as $key => $value) {
            if (in_array($key, $allowedUpdates)) {
                if ($key === 'amount') {
                    $subscription->amount = $value;
                } else {
                    $metadata[$key] = $value;
                    
                    // Recalculate next billing date if frequency changed
                    if ($key === 'frequency') {
                        $metadata['next_billing_date'] = $this->calculateNextBillingDate($value);
                    }
                }
            }
        }

        $subscription->metadata = $metadata;
        $subscription->save();

        return [
            'success' => true,
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription,
        ];
    }

    /**
     * Get active subscriptions for a user.
     */
    public function getUserSubscriptions(int $userId): array
    {
        $subscriptions = Transaction::where('user_id', $userId)
            ->where('is_subscription', true)
            ->where('status', 'active')
            ->get();

        return [
            'success' => true,
            'subscriptions' => $subscriptions,
            'count' => $subscriptions->count(),
        ];
    }

    /**
     * Calculate next billing date based on frequency.
     */
    protected function calculateNextBillingDate(string $frequency, $from = null): string
    {
        $date = $from ? \Carbon\Carbon::parse($from) : now();

        return match($frequency) {
            'daily' => $date->addDay()->toDateString(),
            'weekly' => $date->addWeek()->toDateString(),
            'monthly' => $date->addMonth()->toDateString(),
            'yearly' => $date->addYear()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }

    /**
     * Process due subscriptions (should be called by a scheduled task).
     */
    public function processDueSubscriptions(): array
    {
        $dueSubscriptions = Transaction::where('is_subscription', true)
            ->where('status', 'active')
            ->where('metadata->next_billing_date', '<=', now()->toDateString())
            ->get();

        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($dueSubscriptions as $subscription) {
            $metadata = $subscription->metadata ?? [];
            
            $result = $this->chargeSubscription([
                'user_id' => $subscription->user_id,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'token_id' => $subscription->payment_token_id,
                'description' => $metadata['description'] ?? 'Subscription payment',
            ]);

            $results['processed']++;
            
            if ($result['success']) {
                $results['successful']++;
                
                // Update next billing date
                $metadata['next_billing_date'] = $this->calculateNextBillingDate(
                    $metadata['frequency'],
                    $metadata['next_billing_date']
                );
                $metadata['last_charged_at'] = now()->toDateTimeString();
                $subscription->metadata = $metadata;
                $subscription->save();
            } else {
                $results['failed']++;
                
                // Update failed attempt count
                $metadata['failed_attempts'] = ($metadata['failed_attempts'] ?? 0) + 1;
                $subscription->metadata = $metadata;
                $subscription->save();
                
                // Cancel subscription after 3 failed attempts
                if (($metadata['failed_attempts'] ?? 0) >= 3) {
                    $this->cancelSubscription($subscription);
                }
            }

            $results['details'][] = [
                'subscription_id' => $subscription->id,
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
            ];
        }

        return $results;
    }
}
