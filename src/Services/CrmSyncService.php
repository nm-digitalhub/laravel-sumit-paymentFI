<?php

namespace Sumit\LaravelPayment\Services;

use Illuminate\Support\Facades\Log;
use Sumit\LaravelPayment\Models\Customer;
use Sumit\LaravelPayment\Settings\PaymentSettings;

class CrmSyncService
{
    protected ApiService $apiService;
    protected PaymentSettings $settings;

    public function __construct(ApiService $apiService, PaymentSettings $settings)
    {
        $this->apiService = $apiService;
        $this->settings = $settings;
    }

    /**
     * Fetch customers from SUMIT CRM and sync to local database.
     *
     * @return array
     */
    public function pullCustomersFromCrm(): array
    {
        try {
            $request = [
                'Credentials' => [
                    'CompanyID' => $this->settings->company_id,
                    'APIKey' => $this->settings->api_key,
                ],
            ];

            $response = $this->apiService->post($request, '/website/customers/getlist/', false);

            if ($response === null) {
                return [
                    'success' => false,
                    'message' => 'No response from SUMIT API',
                    'synced' => 0,
                ];
            }

            if (($response['Status'] ?? '') !== 'Success') {
                return [
                    'success' => false,
                    'message' => $response['UserErrorMessage'] ?? 'Failed to fetch customers from CRM',
                    'synced' => 0,
                ];
            }

            $customers = $response['Customers'] ?? [];
            $syncedCount = 0;

            foreach ($customers as $customerData) {
                $this->syncCustomerFromCrm($customerData);
                $syncedCount++;
            }

            return [
                'success' => true,
                'message' => "Successfully synced {$syncedCount} customers from SUMIT CRM",
                'synced' => $syncedCount,
            ];

        } catch (\Exception $e) {
            Log::error('CRM Pull Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while pulling customers from CRM',
                'synced' => 0,
            ];
        }
    }

    /**
     * Push a local customer to SUMIT CRM.
     *
     * @param Customer $customer
     * @return array
     */
    public function pushCustomerToCrm(Customer $customer): array
    {
        try {
            $request = [
                'Credentials' => [
                    'CompanyID' => $this->settings->company_id,
                    'APIKey' => $this->settings->api_key,
                ],
                'Customer' => [
                    'CustomerID' => $customer->sumit_customer_id,
                    'Name' => $customer->name,
                    'Email' => $customer->email,
                    'Phone' => $customer->phone,
                    'CompanyName' => $customer->company_name,
                    'TaxID' => $customer->tax_id,
                    'Address' => $customer->address,
                    'City' => $customer->city,
                    'State' => $customer->state,
                    'Country' => $customer->country,
                    'ZipCode' => $customer->zip_code,
                ],
            ];

            $response = $this->apiService->post($request, '/website/customers/update/', false);

            if ($response === null) {
                return [
                    'success' => false,
                    'message' => 'No response from SUMIT API',
                ];
            }

            if (($response['Status'] ?? '') === 'Success') {
                return [
                    'success' => true,
                    'message' => 'Customer successfully pushed to SUMIT CRM',
                    'response' => $response,
                ];
            }

            return [
                'success' => false,
                'message' => $response['UserErrorMessage'] ?? 'Failed to push customer to CRM',
            ];

        } catch (\Exception $e) {
            Log::error('CRM Push Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while pushing customer to CRM',
            ];
        }
    }

    /**
     * Perform bidirectional sync: pull from CRM then push local changes.
     *
     * @param array $customerIds Optional array of customer IDs to sync
     * @return array
     */
    public function bidirectionalSync(?array $customerIds = null): array
    {
        $results = [
            'pull' => ['success' => false, 'synced' => 0],
            'push' => ['success' => false, 'synced' => 0],
        ];

        // Pull customers from CRM
        $pullResult = $this->pullCustomersFromCrm();
        $results['pull'] = $pullResult;

        // Push local customers to CRM
        $query = Customer::query();
        if ($customerIds) {
            $query->whereIn('id', $customerIds);
        }

        $localCustomers = $query->get();
        $pushSuccessCount = 0;
        $pushFailCount = 0;

        foreach ($localCustomers as $customer) {
            $pushResult = $this->pushCustomerToCrm($customer);
            if ($pushResult['success']) {
                $pushSuccessCount++;
            } else {
                $pushFailCount++;
            }
        }

        $results['push'] = [
            'success' => $pushFailCount === 0,
            'synced' => $pushSuccessCount,
            'failed' => $pushFailCount,
        ];

        return [
            'success' => $results['pull']['success'] && $results['push']['success'],
            'message' => sprintf(
                'Bidirectional sync completed. Pulled: %d, Pushed: %d (Failed: %d)',
                $results['pull']['synced'],
                $results['push']['synced'],
                $results['push']['failed']
            ),
            'details' => $results,
        ];
    }

    /**
     * Sync a single customer from CRM data.
     *
     * @param array $customerData
     * @return Customer
     */
    protected function syncCustomerFromCrm(array $customerData): Customer
    {
        $sumitCustomerId = $customerData['CustomerID'] ?? null;

        if (!$sumitCustomerId) {
            throw new \InvalidArgumentException('Customer data must contain CustomerID');
        }

        return Customer::updateOrCreate(
            ['sumit_customer_id' => $sumitCustomerId],
            [
                'name' => $customerData['Name'] ?? null,
                'email' => $customerData['Email'] ?? null,
                'phone' => $customerData['Phone'] ?? null,
                'company_name' => $customerData['CompanyName'] ?? null,
                'tax_id' => $customerData['TaxID'] ?? null,
                'address' => $customerData['Address'] ?? null,
                'city' => $customerData['City'] ?? null,
                'state' => $customerData['State'] ?? null,
                'country' => $customerData['Country'] ?? 'IL',
                'zip_code' => $customerData['ZipCode'] ?? null,
                'metadata' => array_merge(
                    $customerData['metadata'] ?? [],
                    ['last_synced_at' => now()->toIso8601String()]
                ),
            ]
        );
    }
}
