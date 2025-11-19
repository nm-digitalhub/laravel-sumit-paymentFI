<?php

namespace Sumit\LaravelPayment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Orchestra\Testbench\TestCase;
use Sumit\LaravelPayment\Models\Customer;
use Sumit\LaravelPayment\Services\ApiService;
use Sumit\LaravelPayment\Services\CrmSyncService;
use Sumit\LaravelPayment\Settings\PaymentSettings;
use Sumit\LaravelPayment\SumitPaymentServiceProvider;

class CrmSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [SumitPaymentServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function test_pull_customers_from_crm_success()
    {
        // Mock the ApiService
        $apiServiceMock = Mockery::mock(ApiService::class);
        $apiServiceMock->shouldReceive('post')
            ->once()
            ->andReturn([
                'Status' => 'Success',
                'Customers' => [
                    [
                        'CustomerID' => 'CRM-001',
                        'Name' => 'John Doe',
                        'Email' => 'john@example.com',
                        'Phone' => '+972501234567',
                        'City' => 'Tel Aviv',
                        'Country' => 'IL',
                    ],
                    [
                        'CustomerID' => 'CRM-002',
                        'Name' => 'Jane Smith',
                        'Email' => 'jane@example.com',
                        'Phone' => '+972509876543',
                        'City' => 'Jerusalem',
                        'Country' => 'IL',
                    ],
                ],
            ]);

        // Mock PaymentSettings
        $settingsMock = Mockery::mock(PaymentSettings::class);
        $settingsMock->company_id = 'test-company';
        $settingsMock->api_key = 'test-api-key';

        // Create the service with mocked dependencies
        $crmSync = new CrmSyncService($apiServiceMock, $settingsMock);

        // Execute
        $result = $crmSync->pullCustomersFromCrm();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['synced']);
        $this->assertStringContainsString('2 customers', $result['message']);

        // Verify customers were created in database
        $this->assertDatabaseHas('sumit_customers', [
            'sumit_customer_id' => 'CRM-001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('sumit_customers', [
            'sumit_customer_id' => 'CRM-002',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_pull_customers_from_crm_api_error()
    {
        // Mock the ApiService to return error
        $apiServiceMock = Mockery::mock(ApiService::class);
        $apiServiceMock->shouldReceive('post')
            ->once()
            ->andReturn([
                'Status' => 'Error',
                'UserErrorMessage' => 'Invalid credentials',
            ]);

        // Mock PaymentSettings
        $settingsMock = Mockery::mock(PaymentSettings::class);
        $settingsMock->company_id = 'test-company';
        $settingsMock->api_key = 'test-api-key';

        // Create the service with mocked dependencies
        $crmSync = new CrmSyncService($apiServiceMock, $settingsMock);

        // Execute
        $result = $crmSync->pullCustomersFromCrm();

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['synced']);
        $this->assertStringContainsString('Invalid credentials', $result['message']);
    }

    public function test_push_customer_to_crm_success()
    {
        // Create a customer
        $customer = Customer::create([
            'sumit_customer_id' => 'LOCAL-001',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+972501234567',
            'city' => 'Haifa',
            'country' => 'IL',
        ]);

        // Mock the ApiService
        $apiServiceMock = Mockery::mock(ApiService::class);
        $apiServiceMock->shouldReceive('post')
            ->once()
            ->with(Mockery::on(function ($request) use ($customer) {
                return $request['Customer']['CustomerID'] === $customer->sumit_customer_id
                    && $request['Customer']['Name'] === $customer->name
                    && $request['Customer']['Email'] === $customer->email;
            }), '/website/customers/update/', false)
            ->andReturn([
                'Status' => 'Success',
            ]);

        // Mock PaymentSettings
        $settingsMock = Mockery::mock(PaymentSettings::class);
        $settingsMock->company_id = 'test-company';
        $settingsMock->api_key = 'test-api-key';

        // Create the service with mocked dependencies
        $crmSync = new CrmSyncService($apiServiceMock, $settingsMock);

        // Execute
        $result = $crmSync->pushCustomerToCrm($customer);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully pushed', $result['message']);
    }

    public function test_bidirectional_sync()
    {
        // Create a local customer
        $localCustomer = Customer::create([
            'sumit_customer_id' => 'LOCAL-001',
            'name' => 'Local Customer',
            'email' => 'local@example.com',
            'phone' => '+972501111111',
        ]);

        // Mock the ApiService
        $apiServiceMock = Mockery::mock(ApiService::class);
        
        // Expect pull request
        $apiServiceMock->shouldReceive('post')
            ->once()
            ->with(Mockery::on(function ($request) {
                return isset($request['Credentials']);
            }), '/website/customers/getlist/', false)
            ->andReturn([
                'Status' => 'Success',
                'Customers' => [
                    [
                        'CustomerID' => 'CRM-001',
                        'Name' => 'CRM Customer',
                        'Email' => 'crm@example.com',
                    ],
                ],
            ]);

        // Expect push request for local customer
        $apiServiceMock->shouldReceive('post')
            ->once()
            ->with(Mockery::on(function ($request) {
                return isset($request['Customer']);
            }), '/website/customers/update/', false)
            ->andReturn([
                'Status' => 'Success',
            ]);

        // Mock PaymentSettings
        $settingsMock = Mockery::mock(PaymentSettings::class);
        $settingsMock->company_id = 'test-company';
        $settingsMock->api_key = 'test-api-key';

        // Create the service with mocked dependencies
        $crmSync = new CrmSyncService($apiServiceMock, $settingsMock);

        // Execute
        $result = $crmSync->bidirectionalSync();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['details']['pull']['synced']);
        $this->assertEquals(1, $result['details']['push']['synced']);
        
        // Verify CRM customer was created
        $this->assertDatabaseHas('sumit_customers', [
            'sumit_customer_id' => 'CRM-001',
            'name' => 'CRM Customer',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
