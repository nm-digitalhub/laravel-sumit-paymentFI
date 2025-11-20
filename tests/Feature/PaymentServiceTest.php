<?php

namespace Sumit\LaravelPayment\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Sumit\LaravelPayment\SumitPaymentServiceProvider;
use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Settings\PaymentSettings;
use Sumit\LaravelPayment\Services\PaymentService;
use Sumit\LaravelPayment\Services\ApiService;

class PaymentServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [SumitPaymentServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->seedPaymentSettings();
    }

    protected function seedPaymentSettings(array $overrides = []): void
    {
        /** @var PaymentSettings $settings */
        $settings = app(PaymentSettings::class);

        $defaults = [
            'company_id' => 'test-company',
            'api_key' => 'test-key',
            'api_public_key' => 'test-public-key',
            'merchant_number' => '123456',
            'subscriptions_merchant_number' => '654321',
            'environment' => 'www',
            'testing_mode' => false,
            'pci_mode' => 'direct',
            'email_document' => true,
            'document_language' => 'he',
            'maximum_payments' => 12,
            'draft_document' => false,
            'authorize_only' => false,
            'auto_capture' => true,
            'authorize_added_percent' => 0,
            'authorize_minimum_addition' => 0,
            'token_method' => 'J2',
            'api_timeout' => 180,
            'send_client_ip' => false,
            'vat_included' => true,
            'default_vat_rate' => 17,
        ];

        foreach (array_merge($defaults, $overrides) as $property => $value) {
            $settings->$property = $value;
        }

        $settings->save();
    }

    public function test_can_create_transaction()
    {
        $transaction = Transaction::create([
            'amount' => 100.00,
            'currency' => 'ILS',
            'status' => 'pending',
            'payment_method' => 'credit_card',
        ]);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('pending', $transaction->status);
    }

    public function test_transaction_can_be_marked_as_completed()
    {
        $transaction = Transaction::create([
            'amount' => 100.00,
            'currency' => 'ILS',
            'status' => 'pending',
            'payment_method' => 'credit_card',
        ]);

        $transaction->markAsCompleted('test-transaction-id', 'test-document-id');

        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals('test-transaction-id', $transaction->transaction_id);
        $this->assertEquals('test-document-id', $transaction->document_id);
        $this->assertNotNull($transaction->processed_at);
    }

    public function test_transaction_can_be_marked_as_failed()
    {
        $transaction = Transaction::create([
            'amount' => 100.00,
            'currency' => 'ILS',
            'status' => 'pending',
            'payment_method' => 'credit_card',
        ]);

        $transaction->markAsFailed('Test error message');

        $this->assertEquals('failed', $transaction->status);
        $this->assertEquals('Test error message', $transaction->error_message);
        $this->assertNotNull($transaction->processed_at);
    }

    public function test_payment_service_uses_dynamic_settings_values()
    {
        $this->seedPaymentSettings([
            'company_id' => 'live-company',
            'merchant_number' => '987654',
        ]);

        $mock = \Mockery::mock(ApiService::class);
        $mock->shouldReceive('post')
            ->once()
            ->with(
                \Mockery::on(function (array $payload) {
                    return ($payload['Credentials']['CompanyID'] ?? null) === 'live-company'
                        && ($payload['MerchantNumber'] ?? null) === '987654';
                }),
                '/website/payments/charge/',
                false
            )
            ->andReturn([
                'Status' => 'Success',
                'PaymentID' => 'PAY-1',
                'DocumentID' => 'DOC-1',
            ]);

        $this->app->instance(ApiService::class, $mock);

        /** @var PaymentService $service */
        $service = $this->app->make(PaymentService::class);

        $response = $service->processPayment([
            'amount' => 100,
            'customer_name' => 'Test User',
            'customer_email' => 'user@example.com',
            'card_number' => '4580458045804580',
            'expiry_month' => '12',
            'expiry_year' => '30',
            'cvv' => '123',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('PAY-1', $response['response']['PaymentID']);
    }

    public function test_payment_service_reflects_runtime_setting_changes()
    {
        $this->seedPaymentSettings([
            'company_id' => 'company-initial',
            'merchant_number' => '111111',
        ]);

        $firstMock = \Mockery::mock(ApiService::class);
        $firstMock->shouldReceive('post')
            ->once()
            ->with(
                \Mockery::on(function (array $payload) {
                    return ($payload['Credentials']['CompanyID'] ?? null) === 'company-initial'
                        && ($payload['MerchantNumber'] ?? null) === '111111';
                }),
                '/website/payments/charge/',
                false
            )
            ->andReturn([
                'Status' => 'Success',
            ]);

        $this->app->instance(ApiService::class, $firstMock);

        $service = $this->app->make(PaymentService::class);
        $service->processPayment([
            'amount' => 50,
            'customer_name' => 'First User',
            'customer_email' => 'first@example.com',
            'card_number' => '4580458045804580',
            'expiry_month' => '12',
            'expiry_year' => '30',
            'cvv' => '123',
        ]);

        $this->seedPaymentSettings([
            'company_id' => 'company-updated',
            'merchant_number' => '222222',
        ]);

        $secondMock = \Mockery::mock(ApiService::class);
        $secondMock->shouldReceive('post')
            ->once()
            ->with(
                \Mockery::on(function (array $payload) {
                    return ($payload['Credentials']['CompanyID'] ?? null) === 'company-updated'
                        && ($payload['MerchantNumber'] ?? null) === '222222';
                }),
                '/website/payments/charge/',
                false
            )
            ->andReturn([
                'Status' => 'Success',
            ]);

        $this->app->instance(ApiService::class, $secondMock);

        $service = $this->app->make(PaymentService::class);
        $service->processPayment([
            'amount' => 75,
            'customer_name' => 'Second User',
            'customer_email' => 'second@example.com',
            'card_number' => '4580458045804580',
            'expiry_month' => '11',
            'expiry_year' => '29',
            'cvv' => '555',
        ]);

        $this->assertTrue(true); // Ensures no exceptions thrown
    }

    public function test_capture_transaction_succeeds_for_authorized_transaction()
    {
        // Create an authorized transaction
        $transaction = Transaction::create([
            'amount' => 150.00,
            'currency' => 'ILS',
            'status' => 'authorized',
            'payment_method' => 'credit_card',
            'transaction_id' => 'AUTH-12345',
        ]);

        // Mock the API service
        $mock = \Mockery::mock(ApiService::class);
        $mock->shouldReceive('post')
            ->once()
            ->with(
                \Mockery::on(function (array $payload) {
                    return ($payload['PaymentID'] ?? null) === 'AUTH-12345'
                        && ($payload['Amount'] ?? null) === 150.00;
                }),
                '/website/payments/capture/',
                false
            )
            ->andReturn([
                'Status' => 'Success',
                'PaymentID' => 'PAY-12345',
                'DocumentID' => 'DOC-12345',
            ]);

        $this->app->instance(ApiService::class, $mock);

        /** @var PaymentService $service */
        $service = $this->app->make(PaymentService::class);
        $result = $service->captureTransaction($transaction);

        $this->assertTrue($result['success']);
        $this->assertEquals('Transaction captured successfully', $result['message']);
        
        // Refresh transaction to get updated values
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_capture_transaction_fails_for_non_authorized_transaction()
    {
        // Create a completed transaction (not authorized)
        $transaction = Transaction::create([
            'amount' => 150.00,
            'currency' => 'ILS',
            'status' => 'completed',
            'payment_method' => 'credit_card',
        ]);

        /** @var PaymentService $service */
        $service = $this->app->make(PaymentService::class);
        $result = $service->captureTransaction($transaction);

        $this->assertFalse($result['success']);
        $this->assertEquals('Transaction must be in authorized status to be captured', $result['message']);
    }

    public function test_capture_transaction_handles_api_failure()
    {
        // Create an authorized transaction
        $transaction = Transaction::create([
            'amount' => 150.00,
            'currency' => 'ILS',
            'status' => 'authorized',
            'payment_method' => 'credit_card',
            'transaction_id' => 'AUTH-12345',
        ]);

        // Mock the API service to return failure
        $mock = \Mockery::mock(ApiService::class);
        $mock->shouldReceive('post')
            ->once()
            ->andReturn([
                'Status' => 'Error',
                'UserErrorMessage' => 'Capture failed - insufficient funds',
            ]);

        $this->app->instance(ApiService::class, $mock);

        /** @var PaymentService $service */
        $service = $this->app->make(PaymentService::class);
        $result = $service->captureTransaction($transaction);

        $this->assertFalse($result['success']);
        $this->assertEquals('Capture failed - insufficient funds', $result['message']);
    }
}
