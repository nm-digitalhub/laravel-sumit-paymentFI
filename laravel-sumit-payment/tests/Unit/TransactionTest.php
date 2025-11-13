<?php

namespace NmDigitalHub\LaravelSumitPayment\Tests\Unit;

use NmDigitalHub\LaravelSumitPayment\Tests\TestCase;
use NmDigitalHub\LaravelSumitPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_transaction()
    {
        $transaction = Transaction::create([
            'user_id' => 1,
            'order_id' => 'ORDER-123',
            'payment_id' => 'PAY-456',
            'auth_number' => '789012',
            'amount' => 150.00,
            'currency' => 'ILS',
            'status' => 'completed',
            'valid_payment' => true,
        ]);

        $this->assertDatabaseHas('sumit_transactions', [
            'order_id' => 'ORDER-123',
            'payment_id' => 'PAY-456',
        ]);
    }

    /** @test */
    public function it_can_scope_completed_transactions()
    {
        Transaction::create([
            'order_id' => 'ORDER-1',
            'payment_id' => 'PAY-1',
            'amount' => 100,
            'currency' => 'ILS',
            'status' => 'completed',
            'valid_payment' => true,
        ]);

        Transaction::create([
            'order_id' => 'ORDER-2',
            'payment_id' => 'PAY-2',
            'amount' => 200,
            'currency' => 'ILS',
            'status' => 'failed',
            'valid_payment' => false,
        ]);

        $completed = Transaction::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('ORDER-1', $completed->first()->order_id);
    }

    /** @test */
    public function it_checks_if_transaction_is_successful()
    {
        $successfulTransaction = Transaction::create([
            'order_id' => 'ORDER-1',
            'payment_id' => 'PAY-1',
            'amount' => 100,
            'currency' => 'ILS',
            'status' => 'completed',
            'valid_payment' => true,
        ]);

        $failedTransaction = Transaction::create([
            'order_id' => 'ORDER-2',
            'payment_id' => 'PAY-2',
            'amount' => 100,
            'currency' => 'ILS',
            'status' => 'failed',
            'valid_payment' => false,
        ]);

        $this->assertTrue($successfulTransaction->isSuccessful());
        $this->assertFalse($failedTransaction->isSuccessful());
    }

    /** @test */
    public function it_checks_if_transaction_is_subscription()
    {
        $regularTransaction = Transaction::create([
            'order_id' => 'ORDER-1',
            'payment_id' => 'PAY-1',
            'amount' => 100,
            'currency' => 'ILS',
            'status' => 'completed',
            'valid_payment' => true,
            'is_subscription' => false,
        ]);

        $subscriptionTransaction = Transaction::create([
            'order_id' => 'ORDER-2',
            'payment_id' => 'PAY-2',
            'amount' => 50,
            'currency' => 'ILS',
            'status' => 'completed',
            'valid_payment' => true,
            'is_subscription' => true,
        ]);

        $this->assertFalse($regularTransaction->isSubscription());
        $this->assertTrue($subscriptionTransaction->isSubscription());
    }
}
