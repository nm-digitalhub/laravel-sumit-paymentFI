<?php

namespace Sumit\LaravelPayment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sumit\LaravelPayment\Services\RecurringBillingService;
use Sumit\LaravelPayment\Services\PaymentService;
use Sumit\LaravelPayment\Services\TokenService;
use Sumit\LaravelPayment\Models\Transaction;
use Mockery;

class RecurringBillingServiceTest extends TestCase
{
    protected RecurringBillingService $billingService;
    protected $paymentServiceMock;
    protected $tokenServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentServiceMock = Mockery::mock(PaymentService::class);
        $this->tokenServiceMock = Mockery::mock(TokenService::class);
        $this->billingService = new RecurringBillingService(
            $this->paymentServiceMock,
            $this->tokenServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_instantiate_recurring_billing_service()
    {
        $this->assertInstanceOf(RecurringBillingService::class, $this->billingService);
    }

    public function test_validates_required_fields_for_subscription()
    {
        $result = $this->billingService->createSubscription([
            'user_id' => 1,
            'amount' => 100.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required field', $result['message']);
    }

    public function test_calculate_next_billing_date_daily()
    {
        $reflection = new \ReflectionClass($this->billingService);
        $method = $reflection->getMethod('calculateNextBillingDate');
        $method->setAccessible(true);

        $nextDate = $method->invoke($this->billingService, 'daily');
        
        $expected = now()->addDay()->toDateString();
        $this->assertEquals($expected, $nextDate);
    }

    public function test_calculate_next_billing_date_weekly()
    {
        $reflection = new \ReflectionClass($this->billingService);
        $method = $reflection->getMethod('calculateNextBillingDate');
        $method->setAccessible(true);

        $nextDate = $method->invoke($this->billingService, 'weekly');
        
        $expected = now()->addWeek()->toDateString();
        $this->assertEquals($expected, $nextDate);
    }

    public function test_calculate_next_billing_date_monthly()
    {
        $reflection = new \ReflectionClass($this->billingService);
        $method = $reflection->getMethod('calculateNextBillingDate');
        $method->setAccessible(true);

        $nextDate = $method->invoke($this->billingService, 'monthly');
        
        $expected = now()->addMonth()->toDateString();
        $this->assertEquals($expected, $nextDate);
    }

    public function test_calculate_next_billing_date_yearly()
    {
        $reflection = new \ReflectionClass($this->billingService);
        $method = $reflection->getMethod('calculateNextBillingDate');
        $method->setAccessible(true);

        $nextDate = $method->invoke($this->billingService, 'yearly');
        
        $expected = now()->addYear()->toDateString();
        $this->assertEquals($expected, $nextDate);
    }

    public function test_validates_transaction_is_subscription_for_cancel()
    {
        $transaction = new Transaction([
            'is_subscription' => false,
        ]);

        $result = $this->billingService->cancelSubscription($transaction);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not a subscription', $result['message']);
    }

    public function test_validates_transaction_is_subscription_for_update()
    {
        $transaction = new Transaction([
            'is_subscription' => false,
        ]);

        $result = $this->billingService->updateSubscription($transaction, ['amount' => 150.00]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not a subscription', $result['message']);
    }
}
