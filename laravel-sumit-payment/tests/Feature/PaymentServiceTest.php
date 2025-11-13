<?php

namespace Sumit\LaravelPayment\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Sumit\LaravelPayment\SumitPaymentServiceProvider;
use Sumit\LaravelPayment\Models\Transaction;

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

        // Setup SUMIT config
        $app['config']->set('sumit-payment.company_id', 'test-company');
        $app['config']->set('sumit-payment.api_key', 'test-key');
        $app['config']->set('sumit-payment.api_public_key', 'test-public-key');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
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
}
