<?php

namespace Sumit\LaravelPayment\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ApiService
{
    protected string $companyId;
    protected string $apiKey;
    protected string $apiPublicKey;
    protected string $environment;
    protected Client $client;

    public function __construct(
        string $companyId,
        string $apiKey,
        string $apiPublicKey,
        string $environment = 'www'
    ) {
        $this->companyId = $companyId;
        $this->apiKey = $apiKey;
        $this->apiPublicKey = $apiPublicKey;
        $this->environment = $environment;
        $this->client = new Client([
            'timeout' => config('sumit-payment.api_timeout', 180),
            'verify' => true,
        ]);
    }

    /**
     * Get the API URL for a given path.
     */
    protected function getUrl(string $path): string
    {
        if ($this->environment === 'dev') {
            return 'http://' . $this->environment . '.api.sumit.co.il' . $path;
        }

        return 'https://api.sumit.co.il' . $path;
    }

    /**
     * Get credentials array.
     */
    protected function getCredentials(): array
    {
        return [
            'CompanyID' => $this->companyId,
            'APIKey' => $this->apiKey,
        ];
    }

    /**
     * Get public credentials array.
     */
    protected function getPublicCredentials(): array
    {
        return [
            'CompanyID' => $this->companyId,
            'APIPublicKey' => $this->apiPublicKey,
        ];
    }

    /**
     * Make a POST request to the API.
     */
    public function post(array $request, string $path, bool $sendClientIp = true): ?array
    {
        try {
            $url = $this->getUrl($path);

            // Log request (sanitized)
            $this->logRequest($url, $request);

            $headers = [
                'Content-Type' => 'application/json',
                'Content-Language' => app()->getLocale(),
                'X-OG-Client' => 'Laravel',
            ];

            if ($sendClientIp && request()->ip()) {
                $headers['X-OG-ClientIP'] = request()->ip();
            }

            $response = $this->client->post($url, [
                'json' => $request,
                'headers' => $headers,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // Log response
            $this->logResponse($url, $body);

            return $body;

        } catch (GuzzleException $e) {
            $this->logError('API request failed: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logError('Unexpected error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if credentials are valid.
     */
    public function checkCredentials(): ?string
    {
        $request = [
            'Credentials' => $this->getCredentials(),
        ];

        $response = $this->post($request, '/website/companies/getdetails/', false);

        if ($response === null) {
            return 'No response from server';
        }

        if (($response['Status'] ?? '') === 'Success') {
            return null; // Credentials are valid
        }

        return $response['UserErrorMessage'] ?? 'Invalid credentials';
    }

    /**
     * Check if public credentials are valid.
     */
    public function checkPublicCredentials(): ?string
    {
        $request = [
            'Credentials' => $this->getPublicCredentials(),
            'CardNumber' => '12345678',
            'ExpirationMonth' => '01',
            'ExpirationYear' => date('y'),
        ];

        $response = $this->post($request, '/website/creditcards/tokenize/', false);

        if ($response === null) {
            return 'No response from server';
        }

        // We expect this to fail validation, but if credentials are wrong,
        // we'll get a different error
        if (isset($response['Status'])) {
            return null; // Credentials are valid
        }

        return 'Invalid public credentials';
    }

    /**
     * Log API request (with sensitive data removed).
     */
    protected function logRequest(string $url, array $request): void
    {
        if (!config('sumit-payment.logging.enabled')) {
            return;
        }

        $sanitized = $request;

        // Remove sensitive data
        if (isset($sanitized['PaymentMethod']['CreditCard_Number'])) {
            $sanitized['PaymentMethod']['CreditCard_Number'] = '****';
        }
        if (isset($sanitized['PaymentMethod']['CreditCard_CVV'])) {
            $sanitized['PaymentMethod']['CreditCard_CVV'] = '***';
        }
        if (isset($sanitized['CardNumber'])) {
            $sanitized['CardNumber'] = '****';
        }
        if (isset($sanitized['CVV'])) {
            $sanitized['CVV'] = '***';
        }

        Log::channel(config('sumit-payment.logging.channel'))
            ->debug('SUMIT API Request', [
                'url' => $url,
                'request' => $sanitized,
            ]);
    }

    /**
     * Log API response.
     */
    protected function logResponse(string $url, ?array $response): void
    {
        if (!config('sumit-payment.logging.enabled')) {
            return;
        }

        Log::channel(config('sumit-payment.logging.channel'))
            ->debug('SUMIT API Response', [
                'url' => $url,
                'response' => $response,
            ]);
    }

    /**
     * Log error.
     */
    protected function logError(string $message): void
    {
        if (!config('sumit-payment.logging.enabled')) {
            return;
        }

        Log::channel(config('sumit-payment.logging.channel'))
            ->error('SUMIT API Error', ['message' => $message]);
    }
}
