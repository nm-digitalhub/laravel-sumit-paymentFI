<?php

namespace Sumit\LaravelPayment\Services;

use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Models\Customer;
use Sumit\LaravelPayment\Events\PaymentCompleted;
use Sumit\LaravelPayment\Events\PaymentFailed;
use Illuminate\Support\Facades\Event;

class PaymentService
{
    protected ApiService $apiService;
    protected TokenService $tokenService;

    public function __construct(ApiService $apiService, TokenService $tokenService)
    {
        $this->apiService = $apiService;
        $this->tokenService = $tokenService;
    }

    /**
     * Process a payment.
     */
    public function processPayment(array $paymentData): array
    {
        // Create transaction record
        $transaction = $this->createTransaction($paymentData);

        try {
            // Build API request
            $request = $this->buildPaymentRequest($paymentData, $transaction);

            // Determine payment path based on mode
            $path = $this->getPaymentPath($paymentData);

            // Make API call
            $response = $this->apiService->post($request, $path, config('sumit-payment.send_client_ip', true));

            if (!$response) {
                throw new \Exception('No response from payment gateway');
            }

            // Handle response
            return $this->handlePaymentResponse($response, $transaction);

        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            Event::dispatch(new PaymentFailed($transaction, $e->getMessage()));

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction' => $transaction,
            ];
        }
    }

    /**
     * Process a payment with token.
     */
    public function processPaymentWithToken(array $paymentData, int $tokenId): array
    {
        $token = $this->tokenService->getToken($tokenId, $paymentData['user_id'] ?? null);

        if (!$token || $token->isExpired()) {
            return [
                'success' => false,
                'message' => 'Invalid or expired token',
            ];
        }

        $paymentData['token'] = $token->token;
        $paymentData['use_token'] = true;

        return $this->processPayment($paymentData);
    }

    /**
     * Tokenize a credit card.
     */
    public function tokenizeCard(array $cardData, ?int $userId = null): array
    {
        $request = [
            'Credentials' => [
                'CompanyID' => config('sumit-payment.company_id'),
                'APIPublicKey' => config('sumit-payment.api_public_key'),
            ],
            'CardNumber' => $cardData['card_number'],
            'ExpirationMonth' => $cardData['expiry_month'],
            'ExpirationYear' => $cardData['expiry_year'],
        ];

        if (isset($cardData['cvv'])) {
            $request['CVV'] = $cardData['cvv'];
        }

        $response = $this->apiService->post($request, '/website/creditcards/tokenize/', false);

        if (!$response || ($response['Status'] ?? '') !== 'Success') {
            return [
                'success' => false,
                'message' => $response['UserErrorMessage'] ?? 'Tokenization failed',
            ];
        }

        // Save token if user ID provided
        if ($userId) {
            $token = $this->tokenService->createToken(
                $userId,
                $response['Token'],
                substr($cardData['card_number'], -4),
                $cardData['expiry_month'],
                $cardData['expiry_year'],
                $cardData['card_type'] ?? null,
                $cardData['cardholder_name'] ?? null,
                $cardData['is_default'] ?? false
            );

            return [
                'success' => true,
                'token' => $token,
                'token_id' => $token->id,
            ];
        }

        return [
            'success' => true,
            'token' => $response['Token'],
        ];
    }

    /**
     * Create a transaction record.
     */
    protected function createTransaction(array $paymentData): Transaction
    {
        return Transaction::create([
            'user_id' => $paymentData['user_id'] ?? null,
            'order_id' => $paymentData['order_id'] ?? null,
            'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'ILS',
            'status' => 'pending',
            'payments_count' => $paymentData['payments_count'] ?? 1,
            'description' => $paymentData['description'] ?? null,
            'is_subscription' => $paymentData['is_subscription'] ?? false,
            'is_donation' => $paymentData['is_donation'] ?? false,
            'metadata' => $paymentData['metadata'] ?? null,
        ]);
    }

    /**
     * Build payment request for API.
     */
    protected function buildPaymentRequest(array $paymentData, Transaction $transaction): array
    {
        $request = [
            'Credentials' => [
                'CompanyID' => config('sumit-payment.company_id'),
                'APIKey' => config('sumit-payment.api_key'),
            ],
            'Items' => $this->buildItems($paymentData),
            'VATIncluded' => config('sumit-payment.vat_included') ? 'true' : 'false',
            'VATRate' => $paymentData['vat_rate'] ?? config('sumit-payment.default_vat_rate'),
            'Customer' => $this->buildCustomer($paymentData),
            'AuthoriseOnly' => config('sumit-payment.testing_mode') ? 'true' : 'false',
            'DraftDocument' => config('sumit-payment.draft_document') ? 'true' : 'false',
            'SendDocumentByEmail' => config('sumit-payment.email_document') ? 'true' : 'false',
            'DocumentDescription' => $paymentData['description'] ?? 'Order #' . ($paymentData['order_id'] ?? $transaction->id),
            'Payments_Count' => $paymentData['payments_count'] ?? 1,
            'MaximumPayments' => $this->getMaximumPayments($paymentData['amount']),
            'DocumentLanguage' => $paymentData['language'] ?? config('sumit-payment.document_language'),
            'MerchantNumber' => $this->getMerchantNumber($paymentData),
        ];

        // Add authorization settings if enabled
        if (config('sumit-payment.authorize_only')) {
            $request['AutoCapture'] = config('sumit-payment.auto_capture') ? 'true' : 'false';
            $request['AuthorizeAmount'] = $this->calculateAuthorizeAmount($paymentData['amount']);
        }

        // Add document type for donations
        if ($paymentData['is_donation'] ?? false) {
            $request['DocumentType'] = 'DonationReceipt';
        }

        // Add payment method
        if (isset($paymentData['use_token']) && $paymentData['use_token']) {
            $request['PaymentMethod'] = $this->buildTokenPaymentMethod($paymentData);
        } elseif (config('sumit-payment.pci_mode') === 'redirect') {
            $request['RedirectURL'] = $this->buildRedirectUrl($transaction);
        } else {
            $request['PaymentMethod'] = $this->buildDirectPaymentMethod($paymentData);
        }

        return $request;
    }

    /**
     * Build items array for payment.
     */
    protected function buildItems(array $paymentData): array
    {
        if (isset($paymentData['items'])) {
            return $paymentData['items'];
        }

        // Default single item
        return [
            [
                'Name' => $paymentData['item_name'] ?? 'Payment',
                'Price' => $paymentData['amount'],
                'Quantity' => 1,
            ],
        ];
    }

    /**
     * Build customer data for payment.
     */
    protected function buildCustomer(array $paymentData): array
    {
        $customer = [
            'Name' => $paymentData['customer_name'] ?? '',
            'Email' => $paymentData['customer_email'] ?? '',
        ];

        if (isset($paymentData['customer_phone'])) {
            $customer['Phone'] = $paymentData['customer_phone'];
        }

        if (isset($paymentData['customer_address'])) {
            $customer['Address'] = $paymentData['customer_address'];
        }

        if (isset($paymentData['customer_city'])) {
            $customer['City'] = $paymentData['customer_city'];
        }

        if (isset($paymentData['customer_country'])) {
            $customer['Country'] = $paymentData['customer_country'];
        }

        if (isset($paymentData['customer_zip'])) {
            $customer['ZipCode'] = $paymentData['customer_zip'];
        }

        return $customer;
    }

    /**
     * Build payment method for token payment.
     */
    protected function buildTokenPaymentMethod(array $paymentData): array
    {
        return [
            'CreditCard_Token' => $paymentData['token'],
        ];
    }

    /**
     * Build payment method for direct payment.
     */
    protected function buildDirectPaymentMethod(array $paymentData): array
    {
        return [
            'CreditCard_Number' => $paymentData['card_number'],
            'CreditCard_ExpYear' => $paymentData['expiry_year'],
            'CreditCard_ExpMonth' => $paymentData['expiry_month'],
            'CreditCard_CVV' => $paymentData['cvv'] ?? '',
        ];
    }

    /**
     * Get payment API path based on mode.
     */
    protected function getPaymentPath(array $paymentData): string
    {
        if (config('sumit-payment.pci_mode') === 'redirect') {
            return '/website/payments/beginredirect/';
        }

        if (config('sumit-payment.token_method') === 'J5') {
            return '/website/payments/chargej5/';
        }

        return '/website/payments/charge/';
    }

    /**
     * Get merchant number based on payment type.
     */
    protected function getMerchantNumber(array $paymentData): string
    {
        if ($paymentData['is_subscription'] ?? false) {
            return config('sumit-payment.subscriptions_merchant_number') 
                ?: config('sumit-payment.merchant_number');
        }

        return config('sumit-payment.merchant_number');
    }

    /**
     * Get maximum payments allowed.
     */
    protected function getMaximumPayments(float $amount): int
    {
        // Can be customized based on business logic
        return config('sumit-payment.maximum_payments', 12);
    }

    /**
     * Calculate authorize amount with percentage and minimum.
     */
    protected function calculateAuthorizeAmount(float $amount): float
    {
        $authorizeAmount = $amount;

        $addedPercent = config('sumit-payment.authorize_added_percent', 0);
        if ($addedPercent > 0) {
            $authorizeAmount = round($authorizeAmount * (1 + $addedPercent / 100), 2);
        }

        $minimumAddition = config('sumit-payment.authorize_minimum_addition', 0);
        if ($minimumAddition > 0 && ($authorizeAmount - $amount) < $minimumAddition) {
            $authorizeAmount = round($amount + $minimumAddition, 2);
        }

        return $authorizeAmount;
    }

    /**
     * Build redirect URL for payment callback.
     */
    protected function buildRedirectUrl(Transaction $transaction): string
    {
        return url(config('sumit-payment.routes.callback_url') . '?transaction=' . $transaction->id);
    }

    /**
     * Handle payment response from API.
     */
    protected function handlePaymentResponse(array $response, Transaction $transaction): array
    {
        if (($response['Status'] ?? '') === 'Success') {
            // Update transaction
            $transaction->markAsCompleted(
                $response['PaymentID'] ?? null,
                $response['DocumentID'] ?? null
            );

            if (isset($response['AuthorizationNumber'])) {
                $transaction->authorization_number = $response['AuthorizationNumber'];
            }

            if (isset($response['CustomerID'])) {
                $transaction->customer_id = $response['CustomerID'];
            }

            if (isset($response['LastFourDigits'])) {
                $transaction->last_four_digits = $response['LastFourDigits'];
            }

            $transaction->save();

            // Dispatch event
            Event::dispatch(new PaymentCompleted($transaction));

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction' => $transaction,
                'response' => $response,
            ];
        } else {
            $errorMessage = $response['UserErrorMessage'] ?? 'Payment failed';
            $transaction->markAsFailed($errorMessage);

            // Dispatch event
            Event::dispatch(new PaymentFailed($transaction, $errorMessage));

            return [
                'success' => false,
                'message' => $errorMessage,
                'transaction' => $transaction,
                'response' => $response,
            ];
        }
    }

    /**
     * Process redirect callback.
     */
    public function processRedirectCallback(string $transactionId, array $callbackData): array
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        // Update transaction based on callback data
        if (isset($callbackData['Status']) && $callbackData['Status'] === 'Success') {
            $transaction->markAsCompleted(
                $callbackData['PaymentID'] ?? null,
                $callbackData['DocumentID'] ?? null
            );

            Event::dispatch(new PaymentCompleted($transaction));

            return [
                'success' => true,
                'transaction' => $transaction,
            ];
        } else {
            $transaction->markAsFailed($callbackData['ErrorMessage'] ?? 'Payment failed');
            Event::dispatch(new PaymentFailed($transaction, $callbackData['ErrorMessage'] ?? 'Payment failed'));

            return [
                'success' => false,
                'transaction' => $transaction,
            ];
        }
    }
}
