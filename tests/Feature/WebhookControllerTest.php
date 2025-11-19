<?php

namespace Sumit\LaravelPayment\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Sumit\LaravelPayment\SumitPaymentServiceProvider;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sumit\LaravelPayment\Settings\PaymentSettings;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [SumitPaymentServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
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

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->seedPaymentSettings();
    }

    protected function seedPaymentSettings(array $overrides = []): void
    {
        /** @var PaymentSettings $settings */
        $settings = app(PaymentSettings::class);

        $defaults = [
            'company_id' => 'webhook-company',
            'api_key' => 'webhook-key',
            'api_public_key' => 'webhook-public',
            'merchant_number' => '123456',
            'subscriptions_merchant_number' => '654321',
            'environment' => 'www',
            'testing_mode' => true,
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

    public function test_webhook_endpoint_exists()
    {
        $response = $this->postJson('/sumit/webhook', []);
        
        // Should not return 404
        $this->assertNotEquals(404, $response->status());
    }

    public function test_webhook_handles_payment_completed()
    {
        // Create a test transaction
        $transaction = Transaction::create([
            'transaction_id' => 'TEST123',
            'amount' => 100.00,
            'currency' => 'ILS',
            'status' => 'pending',
        ]);

        $webhookData = [
            'EventType' => 'payment.completed',
            'TransactionID' => 'TEST123',
        ];

        $response = $this->postJson('/sumit/webhook', $webhookData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_webhook_handles_payment_failed()
    {
        $transaction = Transaction::create([
            'transaction_id' => 'TEST456',
            'amount' => 100.00,
            'currency' => 'ILS',
            'status' => 'pending',
        ]);

        $webhookData = [
            'EventType' => 'payment.failed',
            'TransactionID' => 'TEST456',
            'ErrorMessage' => 'Card declined',
        ];

        $response = $this->postJson('/sumit/webhook', $webhookData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_webhook_handles_unknown_event_type()
    {
        $webhookData = [
            'EventType' => 'unknown.event',
            'data' => ['test' => 'value'],
        ];

        $response = $this->postJson('/sumit/webhook', $webhookData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_webhook_signature_respects_dynamic_settings()
    {
        $this->seedPaymentSettings([
            'testing_mode' => false,
            'api_key' => 'signature-key',
        ]);

        $payload = [
            'EventType' => 'payment.completed',
            'TransactionID' => 'SIGNATURE123',
        ];

        $body = json_encode($payload);

        $invalid = $this->call('POST', '/sumit/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SUMIT_SIGNATURE' => 'invalid',
        ], $body);

        $invalid->assertStatus(401);

        $validSignature = hash_hmac('sha256', $body, 'signature-key');

        $valid = $this->call('POST', '/sumit/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SUMIT_SIGNATURE' => $validSignature,
        ], $body);

        $valid->assertStatus(200);
        $valid->assertJson(['success' => true]);
    }
}
