<?php

namespace NmDigitalHub\LaravelOfficeGuy\Services;

use NmDigitalHub\LaravelOfficeGuy\Models\Payment;
use NmDigitalHub\LaravelOfficeGuy\Models\PaymentToken;
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed;
use NmDigitalHub\LaravelOfficeGuy\Events\PaymentFailed;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected OfficeGuyApiService $apiService;

    public function __construct(OfficeGuyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Process a payment.
     */
    public function processPayment(array $paymentData): array
    {
        DB::beginTransaction();

        try {
            // Create payment record
            $payment = $this->createPaymentRecord($paymentData);

            // Build API request
            $request = $this->buildPaymentRequest($paymentData, $payment);

            // Send to API
            $response = $this->apiService->post(
                $request,
                '/creditguy/gateway/transaction/',
                $paymentData['send_client_ip'] ?? false
            );

            if (!$response) {
                throw new \Exception('No response from payment gateway');
            }

            // Process response
            $result = $this->processPaymentResponse($payment, $response, $paymentData);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->apiService->writeToLog('Payment processing error: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create payment record in database.
     */
    protected function createPaymentRecord(array $paymentData): Payment
    {
        return Payment::create([
            'user_id' => $paymentData['user_id'] ?? null,
            'order_id' => $paymentData['order_id'] ?? null,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? config('officeguy.document.currency', 'ILS'),
            'status' => 'pending',
            'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
            'token_id' => $paymentData['token_id'] ?? null,
            'is_subscription_payment' => $paymentData['is_subscription'] ?? false,
            'payments_count' => $paymentData['payments_count'] ?? 1,
            'auto_capture' => $paymentData['auto_capture'] ?? config('officeguy.payment.auto_capture', true),
            'authorize_amount' => $paymentData['authorize_amount'] ?? null,
        ]);
    }

    /**
     * Build payment request for API.
     */
    protected function buildPaymentRequest(array $paymentData, Payment $payment): array
    {
        $request = [
            'Credentials' => $this->apiService->getCredentials(),
            'Items' => $this->getPaymentItems($paymentData),
            'VATIncluded' => config('officeguy.document.vat_included', true) ? 'true' : 'false',
            'VATRate' => $paymentData['vat_rate'] ?? config('officeguy.document.default_vat_rate', 17),
            'Customer' => $this->getCustomerData($paymentData),
            'AuthoriseOnly' => config('officeguy.payment.testing_mode', false) ? 'true' : 'false',
            'DraftDocument' => config('officeguy.payment.draft_document', false) ? 'true' : 'false',
            'SendDocumentByEmail' => config('officeguy.payment.send_document_by_email', true) ? 'true' : 'false',
            'UpdateCustomerOnSuccess' => config('officeguy.customer.update_on_success', true) ? 'true' : 'false',
            'DocumentDescription' => $paymentData['description'] ?? 'Payment',
            'Payments_Count' => $payment->payments_count,
            'MaximumPayments' => $this->getMaximumPayments($payment->amount),
            'DocumentLanguage' => $paymentData['language'] ?? config('officeguy.document.language', 'he'),
        ];

        // Add merchant number
        if ($payment->is_subscription_payment && config('officeguy.payment.subscriptions_merchant_number')) {
            $request['MerchantNumber'] = config('officeguy.payment.subscriptions_merchant_number');
        } elseif (config('officeguy.payment.merchant_number')) {
            $request['MerchantNumber'] = config('officeguy.payment.merchant_number');
        }

        // Add authorization settings
        if (config('officeguy.payment.authorize_only', false)) {
            $request['AutoCapture'] = 'false';
            $request['AuthorizeAmount'] = $this->calculateAuthorizeAmount($payment->amount);
        }

        // Add payment method
        if (isset($paymentData['token_id'])) {
            $token = PaymentToken::find($paymentData['token_id']);
            if ($token) {
                $request['PaymentMethod'] = $this->getPaymentMethodFromToken($token);
            }
        } elseif (isset($paymentData['single_use_token'])) {
            $request['SingleUseToken'] = $paymentData['single_use_token'];
        } elseif (isset($paymentData['card_data'])) {
            $request['PaymentMethod'] = $this->getPaymentMethodFromCardData($paymentData['card_data']);
        }

        // Add redirect URL if needed
        if (isset($paymentData['redirect_url'])) {
            $request['RedirectURL'] = $paymentData['redirect_url'];
        }

        return $request;
    }

    /**
     * Process payment response from API.
     */
    protected function processPaymentResponse(Payment $payment, array $response, array $paymentData): array
    {
        // Store response data
        $payment->update([
            'response_data' => $response,
        ]);

        // Check if payment was successful
        if (isset($response['Status']) && $response['Status'] == 0 && 
            isset($response['Data']['Success']) && $response['Data']['Success'] == true) {
            
            // Update payment record
            $payment->update([
                'status' => 'success',
                'transaction_id' => $response['Data']['TransactionID'] ?? null,
                'document_number' => $response['Data']['DocumentNumber'] ?? null,
                'document_type' => $response['Data']['DocumentType'] ?? 'invoice',
                'captured_at' => now(),
            ]);

            // Create or update token if needed
            $token = null;
            if (isset($paymentData['save_token']) && $paymentData['save_token'] && 
                isset($response['Data']['CardToken'])) {
                $token = $this->createTokenFromResponse($response, $paymentData['user_id']);
            }

            // Fire event
            event(new PaymentProcessed($payment, $response));

            return [
                'success' => true,
                'payment' => $payment,
                'token' => $token,
                'transaction_id' => $response['Data']['TransactionID'] ?? null,
                'document_number' => $response['Data']['DocumentNumber'] ?? null,
            ];
        } else {
            // Payment failed
            $errorMessage = $response['UserErrorMessage'] ?? 
                          ($response['Data']['ResultDescription'] ?? 'Payment failed');

            $payment->markAsFailed($errorMessage);

            // Fire event
            event(new PaymentFailed($payment, $response));

            return [
                'success' => false,
                'error' => $errorMessage,
                'payment' => $payment,
            ];
        }
    }

    /**
     * Get payment items array.
     */
    protected function getPaymentItems(array $paymentData): array
    {
        if (isset($paymentData['items'])) {
            return $paymentData['items'];
        }

        // Default single item
        return [
            [
                'Name' => $paymentData['description'] ?? 'Payment',
                'Quantity' => 1,
                'Price' => $paymentData['amount'],
                'IsPriceIncludeVAT' => config('officeguy.document.vat_included', true),
            ]
        ];
    }

    /**
     * Get customer data array.
     */
    protected function getCustomerData(array $paymentData): array
    {
        return [
            'Name' => $paymentData['customer']['name'] ?? '',
            'Email' => $paymentData['customer']['email'] ?? '',
            'Phone' => $paymentData['customer']['phone'] ?? '',
            'Address' => $paymentData['customer']['address'] ?? '',
            'City' => $paymentData['customer']['city'] ?? '',
            'ZipCode' => $paymentData['customer']['zip_code'] ?? '',
            'ExternalIdentifier' => $paymentData['customer']['external_id'] ?? null,
        ];
    }

    /**
     * Calculate authorize amount with added percentage.
     */
    protected function calculateAuthorizeAmount(float $amount): float
    {
        $authorizeAmount = $amount;
        
        $addedPercent = config('officeguy.payment_limits.authorize_added_percent', 0);
        if ($addedPercent > 0) {
            $authorizeAmount = round($authorizeAmount * (1 + $addedPercent / 100), 2);
        }

        $minimumAddition = config('officeguy.payment_limits.authorize_minimum_addition', 0);
        if ($minimumAddition > 0 && ($authorizeAmount - $amount) < $minimumAddition) {
            $authorizeAmount = round($amount + $minimumAddition, 2);
        }

        return $authorizeAmount;
    }

    /**
     * Get maximum payments allowed.
     */
    protected function getMaximumPayments(float $amount): int
    {
        $maxPayments = config('officeguy.payment_limits.max_payments', 1);
        $minAmountForPayments = config('officeguy.payment_limits.min_amount_for_payments', 0);
        $minAmountPerPayment = config('officeguy.payment_limits.min_amount_per_payment', 0);

        if ($amount < $minAmountForPayments) {
            return 1;
        }

        if ($minAmountPerPayment > 0) {
            $calculatedMax = floor($amount / $minAmountPerPayment);
            return min($maxPayments, $calculatedMax);
        }

        return $maxPayments;
    }

    /**
     * Get payment method from token.
     */
    protected function getPaymentMethodFromToken(PaymentToken $token): array
    {
        return [
            'CardToken' => $token->token,
        ];
    }

    /**
     * Get payment method from card data.
     */
    protected function getPaymentMethodFromCardData(array $cardData): array
    {
        return [
            'CreditCard_Number' => $cardData['number'],
            'CreditCard_CVV' => $cardData['cvv'],
            'CreditCard_ExpirationMonth' => $cardData['expiry_month'],
            'CreditCard_ExpirationYear' => $cardData['expiry_year'],
        ];
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

        $token = PaymentToken::create([
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

        return $token;
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(Payment $payment, float $amount = null): array
    {
        $refundAmount = $amount ?? $payment->amount;

        $request = [
            'Credentials' => $this->apiService->getCredentials(),
            'TransactionID' => $payment->transaction_id,
            'Amount' => $refundAmount,
        ];

        $response = $this->apiService->post($request, '/creditguy/gateway/refund/', false);

        if (!$response) {
            return [
                'success' => false,
                'error' => 'No response from payment gateway',
            ];
        }

        if (isset($response['Status']) && $response['Status'] == 0) {
            return [
                'success' => true,
                'refund_transaction_id' => $response['Data']['RefundTransactionID'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response['UserErrorMessage'] ?? 'Refund failed',
        ];
    }
}
