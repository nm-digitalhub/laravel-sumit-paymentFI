<?php

namespace NmDigitalHub\LaravelSumitPayment\Services;

use NmDigitalHub\LaravelSumitPayment\Models\Transaction;
use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;
use NmDigitalHub\LaravelSumitPayment\Events\PaymentProcessing;
use NmDigitalHub\LaravelSumitPayment\Events\PaymentCompleted;
use NmDigitalHub\LaravelSumitPayment\Events\PaymentFailed;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected SumitApiService $api;
    protected TokenService $tokenService;
    protected InvoiceService $invoiceService;

    /**
     * Create a new PaymentService instance.
     */
    public function __construct(
        SumitApiService $api,
        TokenService $tokenService,
        InvoiceService $invoiceService
    ) {
        $this->api = $api;
        $this->tokenService = $tokenService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Process a payment.
     */
    public function processPayment(array $paymentData): array
    {
        $orderId = $paymentData['order_id'];
        $userId = $paymentData['user_id'] ?? null;

        // Fire processing event
        event(new PaymentProcessing($orderId, $paymentData, $userId));

        // Build request
        $request = $this->buildPaymentRequest($paymentData);

        // Determine endpoint
        $endpoint = $this->getPaymentEndpoint($paymentData);

        // Make API call
        $response = $this->api->post($endpoint, $request, !($paymentData['is_subscription'] ?? false));

        // Handle response
        return $this->handlePaymentResponse($response, $paymentData);
    }

    /**
     * Build payment request.
     */
    protected function buildPaymentRequest(array $paymentData): array
    {
        $request = [];

        // Add items
        $request['Items'] = $paymentData['items'];
        $request['VATIncluded'] = 'true';
        $request['VATRate'] = $paymentData['vat_rate'] ?? '';

        // Add customer
        $request['Customer'] = $this->buildCustomerData($paymentData['customer']);

        // Add payment settings
        $request['AuthoriseOnly'] = config('sumit-payment.testing_mode') ? 'true' : 'false';
        
        if (config('sumit-payment.authorize_only')) {
            $request['AutoCapture'] = 'false';
            $request['AuthorizeAmount'] = $this->calculateAuthorizeAmount($paymentData['amount']);
        }

        $request['DraftDocument'] = config('sumit-payment.draft_document') ? 'true' : 'false';
        $request['SendDocumentByEmail'] = config('sumit-payment.email_document') && !($paymentData['is_subscription'] ?? false) ? 'true' : 'false';
        $request['DocumentDescription'] = $paymentData['description'] ?? 'Order #' . $paymentData['order_id'];
        $request['Payments_Count'] = $paymentData['payments_count'] ?? '1';
        $request['MaximumPayments'] = $this->getMaximumPayments($paymentData['amount']);
        $request['DocumentLanguage'] = $this->getDocumentLanguage();
        $request['MerchantNumber'] = ($paymentData['is_subscription'] ?? false) 
            ? config('sumit-payment.subscription_merchant_number')
            : config('sumit-payment.merchant_number');

        // Add payment method
        if (isset($paymentData['payment_token_id'])) {
            $token = PaymentToken::find($paymentData['payment_token_id']);
            if ($token) {
                $request['PaymentMethod'] = $this->getPaymentMethodFromToken($token, $paymentData);
            }
        } elseif (isset($paymentData['payment_method'])) {
            $request['PaymentMethod'] = $paymentData['payment_method'];
        } elseif (isset($paymentData['single_use_token'])) {
            $request['SingleUseToken'] = $paymentData['single_use_token'];
        }

        // Add redirect URL if needed
        if (config('sumit-payment.pci_compliance') === 'redirect' && isset($paymentData['redirect_url'])) {
            $request['RedirectURL'] = $paymentData['redirect_url'];
        }

        return $request;
    }

    /**
     * Build customer data.
     */
    protected function buildCustomerData(array $customerData): array
    {
        $customer = [
            'Name' => $customerData['name'] ?? 'Guest',
            'EmailAddress' => $customerData['email'] ?? '',
            'City' => $customerData['city'] ?? '',
            'Address' => $customerData['address'] ?? '',
            'ZipCode' => $customerData['zip_code'] ?? '',
            'Phone' => $customerData['phone'] ?? '',
            'ExternalIdentifier' => $customerData['external_id'] ?? '',
            'SearchMode' => config('sumit-payment.merge_customers') ? 'Automatic' : 'None',
        ];

        if (isset($customerData['company_number'])) {
            $customer['CompanyNumber'] = $customerData['company_number'];
        }

        if (isset($customerData['no_vat'])) {
            $customer['NoVAT'] = $customerData['no_vat'];
        }

        // Allow custom hooks to modify customer data
        return $this->applyCustomerFieldsHook($customer, $customerData);
    }

    /**
     * Get payment endpoint based on payment data.
     */
    protected function getPaymentEndpoint(array $paymentData): string
    {
        if ($paymentData['is_subscription'] ?? false) {
            return '/billing/recurring/charge/';
        }

        if (config('sumit-payment.pci_compliance') === 'redirect') {
            return '/billing/payments/beginredirect/';
        }

        return '/billing/payments/charge/';
    }

    /**
     * Handle payment response.
     */
    protected function handlePaymentResponse(array $response, array $paymentData): array
    {
        // Handle redirect flow
        if (config('sumit-payment.pci_compliance') === 'redirect') {
            if (isset($response['Data']['RedirectURL'])) {
                return [
                    'success' => true,
                    'redirect_url' => $response['Data']['RedirectURL'],
                ];
            }
            
            event(new PaymentFailed(
                $paymentData['order_id'],
                'Redirect URL not received',
                $response,
                $paymentData['user_id'] ?? null
            ));

            return [
                'success' => false,
                'error' => 'Something went wrong',
            ];
        }

        // Handle standard payment flow
        if ($response['Status'] == 0 && ($response['Data']['Payment']['ValidPayment'] ?? false)) {
            return $this->handleSuccessfulPayment($response, $paymentData);
        }

        // Handle failed payment
        return $this->handleFailedPayment($response, $paymentData);
    }

    /**
     * Handle successful payment.
     */
    protected function handleSuccessfulPayment(array $response, array $paymentData): array
    {
        $paymentResponse = $response['Data']['Payment'];
        $paymentMethod = $paymentResponse['PaymentMethod'];

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $paymentData['user_id'] ?? null,
            'order_id' => $paymentData['order_id'],
            'payment_id' => $paymentResponse['ID'],
            'auth_number' => $paymentResponse['AuthNumber'],
            'amount' => $paymentResponse['Amount'],
            'first_payment_amount' => $paymentResponse['FirstPaymentAmount'] ?? null,
            'non_first_payment_amount' => $paymentResponse['NonFirstPaymentAmount'] ?? null,
            'currency' => $paymentData['currency'] ?? 'ILS',
            'payments_count' => $paymentData['payments_count'] ?? 1,
            'status' => 'completed',
            'valid_payment' => true,
            'card_last_four' => $paymentMethod['CreditCard_LastDigits'] ?? null,
            'card_expiry_month' => $paymentMethod['CreditCard_ExpirationMonth'] ?? null,
            'card_expiry_year' => $paymentMethod['CreditCard_ExpirationYear'] ?? null,
            'document_id' => $response['Data']['DocumentID'] ?? null,
            'customer_id' => $response['Data']['CustomerID'] ?? null,
            'is_subscription' => $paymentData['is_subscription'] ?? false,
            'is_test' => config('sumit-payment.testing_mode'),
            'metadata' => $paymentData['metadata'] ?? null,
        ]);

        // Handle token storage if needed
        if (isset($paymentData['save_token']) && $paymentData['save_token'] && isset($paymentMethod['CreditCard_Token'])) {
            $token = $this->tokenService->createToken([
                'user_id' => $paymentData['user_id'],
                'token' => $paymentMethod['CreditCard_Token'],
                'last_four' => $paymentMethod['CreditCard_LastDigits'],
                'expiry_month' => $paymentMethod['CreditCard_ExpirationMonth'],
                'expiry_year' => $paymentMethod['CreditCard_ExpirationYear'],
                'citizen_id' => $paymentMethod['CreditCard_CitizenID'] ?? null,
            ]);

            $transaction->payment_token_id = $token->id;
            $transaction->save();
        }

        // Fire success event
        event(new PaymentCompleted($transaction, $response));

        // Create order document if configured
        if (config('sumit-payment.create_order_document') && isset($response['Data']['DocumentID'])) {
            $this->invoiceService->createOrderDocument(
                $paymentData['order_id'],
                $response['Data']['CustomerID'],
                $response['Data']['DocumentID'],
                $paymentData
            );
        }

        return [
            'success' => true,
            'transaction' => $transaction,
            'payment_id' => $paymentResponse['ID'],
            'auth_number' => $paymentResponse['AuthNumber'],
            'document_id' => $response['Data']['DocumentID'] ?? null,
            'customer_id' => $response['Data']['CustomerID'] ?? null,
        ];
    }

    /**
     * Handle failed payment.
     */
    protected function handleFailedPayment(array $response, array $paymentData): array
    {
        $errorMessage = $response['UserErrorMessage'] ?? 
            ($response['Data']['Payment']['StatusDescription'] ?? 'Unknown error');

        // Create failed transaction record
        Transaction::create([
            'user_id' => $paymentData['user_id'] ?? null,
            'order_id' => $paymentData['order_id'],
            'payment_id' => $response['Data']['Payment']['ID'] ?? null,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'ILS',
            'status' => 'failed',
            'status_description' => $errorMessage,
            'valid_payment' => false,
            'is_test' => config('sumit-payment.testing_mode'),
        ]);

        // Fire failed event
        event(new PaymentFailed(
            $paymentData['order_id'],
            $errorMessage,
            $response,
            $paymentData['user_id'] ?? null
        ));

        return [
            'success' => false,
            'error' => $errorMessage,
        ];
    }

    /**
     * Get payment method from token.
     */
    protected function getPaymentMethodFromToken(PaymentToken $token, array $paymentData): array
    {
        return [
            'CreditCard_Token' => $token->token,
            'CreditCard_CVV' => $paymentData['cvv'] ?? '',
            'CreditCard_CitizenID' => $token->citizen_id,
            'CreditCard_ExpirationMonth' => $token->expiry_month,
            'CreditCard_ExpirationYear' => $token->expiry_year,
            'Type' => 1,
        ];
    }

    /**
     * Calculate authorize amount.
     */
    protected function calculateAuthorizeAmount(float $orderAmount): float
    {
        $authorizeAmount = $orderAmount;
        
        $addedPercent = config('sumit-payment.authorize_added_percent');
        if ($addedPercent > 0) {
            $authorizeAmount = round($authorizeAmount * (1 + $addedPercent / 100), 2);
        }

        $minimumAddition = config('sumit-payment.authorize_minimum_addition');
        if ($minimumAddition > 0 && ($authorizeAmount - $orderAmount) < $minimumAddition) {
            $authorizeAmount = round($orderAmount + $minimumAddition, 2);
        }

        return $authorizeAmount;
    }

    /**
     * Get maximum payments.
     */
    protected function getMaximumPayments(float $amount): int
    {
        $maxPayments = config('sumit-payment.max_payments', 12);
        
        $minAmountPerPayment = config('sumit-payment.min_amount_per_payment', 0);
        if ($minAmountPerPayment > 0) {
            $maxPayments = min($maxPayments, (int)floor($amount / $minAmountPerPayment));
        }

        $minAmountForPayments = config('sumit-payment.min_amount_for_payments', 0);
        if ($minAmountForPayments > 0 && $amount < $minAmountForPayments) {
            $maxPayments = 1;
        }

        // Allow custom hook to modify maximum payments
        return $this->applyMaximumInstallmentsHook($maxPayments, $amount);
    }

    /**
     * Get document language.
     */
    protected function getDocumentLanguage(): string
    {
        if (!config('sumit-payment.automatic_languages')) {
            return '';
        }

        $locale = app()->getLocale();
        
        return match ($locale) {
            'en', 'en_US' => 'English',
            'ar', 'ar_AR' => 'Arabic',
            'es', 'es_ES' => 'Spanish',
            'he', 'he_IL' => 'Hebrew',
            default => '',
        };
    }

    /**
     * Apply custom customer fields hook.
     */
    protected function applyCustomerFieldsHook(array $customer, array $originalData): array
    {
        $event = new \NmDigitalHub\LaravelSumitPayment\Events\Hooks\CustomerFields($customer, $originalData);
        event($event);
        return $event->getValue();
    }

    /**
     * Apply custom maximum installments hook.
     */
    protected function applyMaximumInstallmentsHook(int $maxPayments, float $amount): int
    {
        $event = new \NmDigitalHub\LaravelSumitPayment\Events\Hooks\MaximumInstallments($maxPayments, $amount);
        event($event);
        return $event->getValue();
    }

    /**
     * Process refund.
     */
    public function processRefund(string $orderId, float $amount, ?string $description = null): array
    {
        $transaction = Transaction::where('order_id', $orderId)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaction not found',
            ];
        }

        $token = $transaction->paymentToken;
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Payment token not found',
            ];
        }

        // Build refund request
        $request = [
            'Items' => [
                [
                    'Item' => [
                        'Name' => $description ?? 'Refund',
                        'SearchMode' => 'Automatic',
                    ],
                    'UnitPrice' => -round($amount, 2),
                    'Currency' => $transaction->currency,
                ],
            ],
            'SupportCredit' => 'true',
            'VATIncluded' => 'true',
            'Customer' => ['ID' => $transaction->customer_id],
            'PaymentMethod' => $this->getPaymentMethodFromToken($token, []),
        ];

        $response = $this->api->post('/billing/payments/charge/', $request, false);

        if ($response['Status'] == 0 && ($response['Data']['Payment']['ValidPayment'] ?? false)) {
            return [
                'success' => true,
                'refund_id' => $response['Data']['Payment']['ID'],
                'document_id' => $response['Data']['DocumentID'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response['UserErrorMessage'] ?? 'Refund failed',
        ];
    }
}
