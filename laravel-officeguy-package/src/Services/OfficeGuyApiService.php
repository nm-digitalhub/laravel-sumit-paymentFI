<?php

namespace NmDigitalHub\LaravelOfficeGuy\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OfficeGuyApiService
{
    protected string $companyId;
    protected string $apiPrivateKey;
    protected string $apiPublicKey;
    protected string $environment;
    protected Client $httpClient;

    /**
     * Constructor.
     */
    public function __construct(
        string $companyId,
        string $apiPrivateKey,
        string $apiPublicKey,
        string $environment = 'www'
    ) {
        $this->companyId = $companyId;
        $this->apiPrivateKey = $apiPrivateKey;
        $this->apiPublicKey = $apiPublicKey;
        $this->environment = $environment ?: 'www';
        
        $this->httpClient = new Client([
            'timeout' => config('officeguy.api.timeout', 180),
            'verify' => config('officeguy.api.verify_ssl', true),
        ]);
    }

    /**
     * Get API URL for a given path.
     */
    public function getUrl(string $path): string
    {
        if ($this->environment === 'dev') {
            return 'http://dev.api.sumit.co.il' . $path;
        }
        
        return 'https://api.sumit.co.il' . $path;
    }

    /**
     * Send POST request to the API.
     */
    public function post(array $request, string $path, bool $sendClientIp = false): ?array
    {
        try {
            $response = $this->postRaw($request, $path, $sendClientIp);
            
            if (!$response) {
                return null;
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            $this->writeToLog('API Error: ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Send raw POST request to the API.
     */
    public function postRaw(array $request, string $path, bool $sendClientIp = false): ?string
    {
        $url = $this->getUrl($path);

        // Sanitize request for logging
        $requestLog = $request;
        if (isset($requestLog['PaymentMethod'])) {
            $requestLog['PaymentMethod']['CreditCard_Number'] = '****';
            $requestLog['PaymentMethod']['CreditCard_CVV'] = '***';
        }
        $requestLog['CardNumber'] = '****';
        $requestLog['CVV'] = '***';

        $this->writeToLog('Request: ' . $url . "\n" . json_encode($requestLog, JSON_PRETTY_PRINT), 'debug');

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Content-Language' => config('app.locale', 'he'),
                'X-OG-Client' => 'Laravel',
            ];

            if ($sendClientIp && request()) {
                $headers['X-OG-ClientIP'] = request()->ip();
            }

            $response = $this->httpClient->post($url, [
                'headers' => $headers,
                'json' => $request,
            ]);

            $body = $response->getBody()->getContents();
            $this->writeToLog('Response: ' . $url . "\n" . $body, 'debug');

            return $body;
        } catch (GuzzleException $e) {
            $this->writeToLog('Connection Error: ' . $url . ' - ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Check if credentials are valid.
     */
    public function checkCredentials(string $companyId = null, string $apiKey = null): ?string
    {
        $credentials = [
            'CompanyID' => $companyId ?? $this->companyId,
            'APIKey' => $apiKey ?? $this->apiPrivateKey,
        ];

        $request = ['Credentials' => $credentials];
        $response = $this->post($request, '/website/companies/getdetails/', false);

        if ($response === null) {
            return 'No response from server';
        }

        if (isset($response['Status']) && $response['Status'] === 'Success') {
            return null; // Credentials are valid
        }

        return $response['UserErrorMessage'] ?? 'Invalid credentials';
    }

    /**
     * Check if public credentials are valid.
     */
    public function checkPublicCredentials(string $companyId = null, string $apiPublicKey = null): ?string
    {
        $credentials = [
            'CompanyID' => $companyId ?? $this->companyId,
            'APIPublicKey' => $apiPublicKey ?? $this->apiPublicKey,
        ];

        $request = [
            'Credentials' => $credentials,
            'CardNumber' => '12345678',
            'ExpirationMonth' => '01',
            'ExpirationYear' => date('Y', strtotime('+1 year')),
            'CVV' => '123',
        ];

        $response = $this->post($request, '/creditguy/gateway/getsingleusetoken/', false);

        if ($response === null) {
            return 'No response from server';
        }

        if (isset($response['Status']) && $response['Status'] === 0) {
            return null; // Public credentials are valid
        }

        return $response['UserErrorMessage'] ?? 'Invalid public credentials';
    }

    /**
     * Get credentials array for API requests.
     */
    public function getCredentials(): array
    {
        return [
            'CompanyID' => $this->companyId,
            'APIKey' => $this->apiPrivateKey,
        ];
    }

    /**
     * Get public credentials array for API requests.
     */
    public function getPublicCredentials(): array
    {
        return [
            'CompanyID' => $this->companyId,
            'APIPublicKey' => $this->apiPublicKey,
        ];
    }

    /**
     * Write message to log.
     */
    public function writeToLog(string $message, string $level = 'info'): void
    {
        if (!config('officeguy.logging.enabled', true)) {
            return;
        }

        $channel = config('officeguy.logging.channel', 'stack');
        
        Log::channel($channel)->log($level, '[OfficeGuy] ' . $message);
    }

    /**
     * Get supported currencies.
     */
    public function getSupportedCurrencies(): array
    {
        return config('officeguy.supported_currencies', ['ILS', 'USD', 'EUR', 'GBP']);
    }

    /**
     * Check if currency is supported.
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies());
    }
}
