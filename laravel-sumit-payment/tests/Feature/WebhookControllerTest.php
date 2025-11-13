<?php

namespace Sumit\LaravelPayment\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Sumit\LaravelPayment\SumitPaymentServiceProvider;
use Sumit\LaravelPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        
        $app['config']->set('sumit-payment.testing_mode', true);
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
}
