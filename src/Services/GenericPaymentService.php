<?php

namespace Sumit\LaravelPayment\Services;

use Sumit\LaravelPayment\Contracts\PaymentGatewayInterface;
use Sumit\LaravelPayment\DTO\PaymentData;
use Sumit\LaravelPayment\DTO\PaymentResponse;
use Sumit\LaravelPayment\Events\PaymentCreated;
use Sumit\LaravelPayment\Events\PaymentCompleted;
use Sumit\LaravelPayment\Events\PaymentFailed;
use Sumit\LaravelPayment\Settings\PaymentSettings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Generic Payment Service
 * 
 * This is the model-agnostic implementation of the payment gateway.
 * It only fires events and does NOT write to any database.
 * Users can listen to events and handle data storage in their own way.
 */
class GenericPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        protected ApiService $apiService,
        protected PaymentSettings $settings
    ) {}

    /**
     * Create a payment
     *
     * @param PaymentData $data Payment data
     * @return PaymentResponse Payment response
     */
    public function createPayment(PaymentData $data): PaymentResponse
    {
        // Execute before_payment callback if defined
        if ($callback = config('sumit-payment.callbacks.before_payment')) {
            $callback($data);
        }

        try {
            // Generate transaction ID
            $transactionId = $this->generateTransactionId();

            // Build API request
            $request = $this->buildPaymentRequest($data, $transactionId);

            // Determine payment path based on mode
            $path = $this->getPaymentPath($data);

            // Make API call
            $apiResponse = $this->apiService->post($request, $path, $this->settings->send_client_ip);

            if (!$apiResponse) {
                throw new \Exception('No response from payment gateway');
            }

            // Build response DTO
            $response = $this->buildPaymentResponse($apiResponse, $transactionId);

            // Fire PaymentCreated event
            Event::dispatch(new PaymentCreated($response, $data));

            // Handle response status
            if ($response->isSuccessful()) {
                // Fire PaymentCompleted event
                Event::dispatch(new PaymentCompleted(
                    transactionId: $response->transactionId,
                    amount: $data->amount,
                    currency: $data->currency,
                    documentId: $response->documentId,
                    authorizationNumber: $response->authorizationNumber,
                    customerId: $response->customerId,
                    metadata: $data->metadata
                ));

                // Execute after_payment_success callback if defined
                if ($callback = config('sumit-payment.callbacks.after_payment_success')) {
                    $callback($response);
                }
            } elseif ($response->hasFailed()) {
                // Fire PaymentFailed event
                Event::dispatch(new PaymentFailed(
                    transactionId: $response->transactionId,
                    errorMessage: $response->errorMessage ?? 'Payment failed',
                    amount: $data->amount,
                    metadata: $data->metadata
                ));

                // Execute after_payment_failure callback if defined
                if ($callback = config('sumit-payment.callbacks.after_payment_failure')) {
                    $callback($response->errorMessage ?? 'Payment failed', $data);
                }
            }

            return $response;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $transactionId = $transactionId ?? $this->generateTransactionId();

            // Fire PaymentFailed event
            Event::dispatch(new PaymentFailed(
                transactionId: $transactionId,
                errorMessage: $errorMessage,
                amount: $data->amount,
                metadata: $data->metadata
            ));

            // Execute after_payment_failure callback if defined
            if ($callback = config('sumit-payment.callbacks.after_payment_failure')) {
                $callback($errorMessage, $data);
            }

            return new PaymentResponse(
                transactionId: $transactionId,
                status: 'failed',
                errorMessage: $errorMessage
            );
        }
    }

    /**
     * Get transaction status
     *
     * @param string $transactionId Transaction ID
     * @return PaymentResponse Payment response
     */
    public function getTransactionStatus(string $transactionId): PaymentResponse
    {
        try {
            $response = $this->apiService->get("/payment/{$transactionId}");

            return new PaymentResponse(
                transactionId: $transactionId,
                status: $this->mapStatus($response['status'] ?? 'unknown'),
                documentId: $response['document_id'] ?? null,
                rawResponse: $response
            );
        } catch (\Exception $e) {
            return new PaymentResponse(
                transactionId: $transactionId,
                status: 'unknown',
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Refund a transaction
     *
     * @param string $transactionId Transaction ID
     * @param float $amount Refund amount
     * @param string|null $reason Refund reason
     * @return PaymentResponse Payment response
     */
    public function refund(string $transactionId, float $amount, ?string $reason = null): PaymentResponse
    {
        try {
            $request = [
                'Credentials' => [
                    'CompanyID' => $this->settings->company_id,
                    'APIKey' => $this->settings->api_key,
                ],
                'TransactionID' => $transactionId,
                'Amount' => $amount,
            ];

            if ($reason) {
                $request['Reason'] = $reason;
            }

            $response = $this->apiService->post($request, '/payment/refund/', $this->settings->send_client_ip);

            if (!$response || ($response['Status'] ?? '') !== 'Success') {
                throw new \Exception($response['UserErrorMessage'] ?? 'Refund failed');
            }

            return new PaymentResponse(
                transactionId: $transactionId,
                status: 'refunded',
                documentId: $response['RefundDocumentID'] ?? null,
                rawResponse: $response
            );

        } catch (\Exception $e) {
            return new PaymentResponse(
                transactionId: $transactionId,
                status: 'refund_failed',
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Build payment request for API
     */
    protected function buildPaymentRequest(PaymentData $data, string $transactionId): array
    {
        $request = [
            'Credentials' => [
                'CompanyID' => $this->settings->company_id,
                'APIKey' => $this->settings->api_key,
            ],
            'Items' => $this->buildItems($data),
            'VATIncluded' => $this->settings->vat_included ? 'true' : 'false',
            'VATRate' => $this->settings->default_vat_rate,
            'Customer' => $this->buildCustomer($data),
            'AuthoriseOnly' => $this->settings->testing_mode ? 'true' : 'false',
            'DraftDocument' => $this->settings->draft_document ? 'true' : 'false',
            'SendDocumentByEmail' => $this->settings->email_document ? 'true' : 'false',
            'DocumentDescription' => $data->description ?? 'Payment',
            'Payments_Count' => $data->paymentsCount,
            'MaximumPayments' => $this->settings->maximum_payments,
            'DocumentLanguage' => $data->language ?? $this->settings->document_language,
            'MerchantNumber' => $this->getMerchantNumber($data),
        ];

        // Add document type for donations
        if ($data->isDonation) {
            $request['DocumentType'] = 'DonationReceipt';
        }

        // Add payment method
        if ($data->token) {
            $request['PaymentMethod'] = [
                'CreditCard_Token' => $data->token,
            ];
        } elseif ($this->settings->pci_mode === 'redirect') {
            $request['RedirectURL'] = $this->buildRedirectUrl($transactionId);
        } else {
            $request['PaymentMethod'] = [
                'CreditCard_Number' => $data->cardNumber,
                'CreditCard_ExpYear' => $data->expiryYear,
                'CreditCard_ExpMonth' => $data->expiryMonth,
                'CreditCard_CVV' => $data->cvv ?? '',
            ];
        }

        return $request;
    }

    /**
     * Build items array for payment
     */
    protected function buildItems(PaymentData $data): array
    {
        if ($data->items) {
            return $data->items;
        }

        return [
            [
                'Name' => $data->itemName ?? 'Payment',
                'Price' => $data->amount,
                'Quantity' => 1,
            ],
        ];
    }

    /**
     * Build customer data for payment
     */
    protected function buildCustomer(PaymentData $data): array
    {
        $customer = [
            'Name' => $data->customerName ?? '',
            'Email' => $data->customerEmail ?? '',
        ];

        if ($data->customerPhone) {
            $customer['Phone'] = $data->customerPhone;
        }

        if ($data->customerAddress) {
            $customer['Address'] = $data->customerAddress;
        }

        if ($data->customerCity) {
            $customer['City'] = $data->customerCity;
        }

        if ($data->customerCountry) {
            $customer['Country'] = $data->customerCountry;
        }

        if ($data->customerZip) {
            $customer['ZipCode'] = $data->customerZip;
        }

        return $customer;
    }

    /**
     * Get payment API path based on mode
     */
    protected function getPaymentPath(PaymentData $data): string
    {
        if ($this->settings->pci_mode === 'redirect') {
            return '/website/payments/beginredirect/';
        }

        if ($this->settings->token_method === 'J5') {
            return '/website/payments/chargej5/';
        }

        return '/website/payments/charge/';
    }

    /**
     * Get merchant number based on payment type
     */
    protected function getMerchantNumber(PaymentData $data): string
    {
        if ($data->isSubscription) {
            return $this->settings->subscriptions_merchant_number 
                ?: $this->settings->merchant_number;
        }

        return $this->settings->merchant_number;
    }

    /**
     * Build redirect URL for payment callback
     */
    protected function buildRedirectUrl(string $transactionId): string
    {
        return url(config('sumit-payment.routes.callback_url') . '?transaction=' . $transactionId);
    }

    /**
     * Build payment response from API response
     */
    protected function buildPaymentResponse(array $apiResponse, string $transactionId): PaymentResponse
    {
        $status = $this->mapStatus($apiResponse['Status'] ?? 'unknown');

        return new PaymentResponse(
            transactionId: $apiResponse['PaymentID'] ?? $transactionId,
            paymentUrl: $apiResponse['RedirectURL'] ?? null,
            status: $status,
            documentId: $apiResponse['DocumentID'] ?? null,
            authorizationNumber: $apiResponse['AuthorizationNumber'] ?? null,
            lastFourDigits: $apiResponse['LastFourDigits'] ?? null,
            customerId: $apiResponse['CustomerID'] ?? null,
            errorMessage: $apiResponse['UserErrorMessage'] ?? null,
            rawResponse: $apiResponse
        );
    }

    /**
     * Map SUMIT status to our status
     */
    protected function mapStatus(string $sumitStatus): string
    {
        return match(strtolower($sumitStatus)) {
            'success' => 'completed',
            'pending' => 'pending',
            'failed', 'error' => 'failed',
            default => 'unknown',
        };
    }

    /**
     * Generate unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return 'TXN-' . time() . '-' . Str::random(8);
    }
}
