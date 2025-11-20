<?php

namespace NmDigitalHub\LaravelSumitPayment\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SumitApiService
{
    protected Client $client;
    protected string $companyId;
    protected string $apiKey;
    protected string $environment;
    protected string $baseUrl;

    /**
     * Create a new SumitApiService instance.
     */
    public function __construct(string $companyId, string $apiKey, string $environment = 'www')
    {
        $this->companyId = $companyId;
        $this->apiKey = $apiKey;
        $this->environment = $environment;
        $this->baseUrl = $this->getBaseUrl();
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 180,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get the base URL based on environment.
     */
    protected function getBaseUrl(): string
    {
        if ($this->environment === 'dev') {
            return 'http://dev.api.sumit.co.il';
        }
        
        return 'https://api.sumit.co.il';
    }

    /**
     * Get API credentials.
     */
    protected function getCredentials(): array
    {
        return [
            'CompanyID' => $this->companyId,
            'APIKey' => $this->apiKey,
        ];
    }

    /**
     * Make a POST request to the SUMIT API.
     */
    public function post(string $path, array $data = [], bool $sendClientIp = false): ?array
    {
        try {
            // Add credentials to request
            $data['Credentials'] = $this->getCredentials();

            // Prepare headers
            $headers = [
                'Content-Language' => app()->getLocale(),
                'X-OG-Client' => 'Laravel',
            ];

            if ($sendClientIp && request()) {
                $headers['X-OG-ClientIP'] = request()->ip();
            }

            // Log request (without sensitive data)
            $this->logRequest($path, $data);

            // Make request
            $response = $this->client->post($path, [
                'json' => $data,
                'headers' => $headers,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // Log response
            $this->logResponse($path, $body);

            return $body;

        } catch (GuzzleException $e) {
            $this->logError($path, $e->getMessage());
            
            return [
                'Status' => -1,
                'UserErrorMessage' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate API credentials.
     */
    public function validateCredentials(): ?string
    {
        $request = [
            'Credentials' => $this->getCredentials(),
        ];

        $response = $this->post('/website/companies/getdetails/', $request, false);

        if ($response === null) {
            return 'No response from server';
        }

        if (isset($response['Status']) && $response['Status'] === 'Success') {
            return null;
        }

        return $response['UserErrorMessage'] ?? 'Unknown error';
    }

    /**
     * Validate public API credentials.
     */
    public function validatePublicCredentials(string $apiPublicKey): ?string
    {
        $request = [
            'Credentials' => [
                'CompanyID' => $this->companyId,
                'APIPublicKey' => $apiPublicKey,
            ],
            'CardNumber' => '12345678',
            'ExpirationMonth' => '01',
            'ExpirationYear' => '2030',
            'CVV' => '123',
            'CitizenID' => '123456789',
        ];

        $response = $this->post('/creditguy/vault/tokenizesingleusejson/', $request, false);

        if ($response === null) {
            return 'No response from server';
        }

        if (isset($response['Status']) && $response['Status'] === 'Success') {
            return null;
        }

        return $response['UserErrorMessage'] ?? 'Unknown error';
    }

    /**
     * Log API request.
     */
    protected function logRequest(string $path, array $data): void
    {
        if (!config('sumit-payment.logging')) {
            return;
        }

        // Remove sensitive data from log
        $logData = $data;
        if (isset($logData['PaymentMethod'])) {
            $logData['PaymentMethod']['CreditCard_Number'] = '****';
            $logData['PaymentMethod']['CreditCard_CVV'] = '***';
        }
        unset($logData['CardNumber'], $logData['CVV']);

        Log::channel(config('sumit-payment.log_channel'))
            ->debug('SUMIT API Request', [
                'url' => $this->baseUrl . $path,
                'data' => $logData,
            ]);
    }

    /**
     * Log API response.
     */
    protected function logResponse(string $path, ?array $response): void
    {
        if (!config('sumit-payment.logging')) {
            return;
        }

        Log::channel(config('sumit-payment.log_channel'))
            ->debug('SUMIT API Response', [
                'url' => $this->baseUrl . $path,
                'response' => $response,
            ]);
    }

    /**
     * Log API error.
     */
    protected function logError(string $path, string $error): void
    {
        Log::channel(config('sumit-payment.log_channel'))
            ->error('SUMIT API Error', [
                'url' => $this->baseUrl . $path,
                'error' => $error,
            ]);
    }
}
