<?php

namespace Sumit\LaravelPayment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sumit\LaravelPayment\Services\RefundService;
use Sumit\LaravelPayment\Services\ApiService;
use Sumit\LaravelPayment\Models\Transaction;
use Mockery;

class RefundServiceTest extends TestCase
{
    protected RefundService $refundService;
    protected $apiServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->apiServiceMock = Mockery::mock(ApiService::class);
        $this->refundService = new RefundService($this->apiServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_instantiate_refund_service()
    {
        $this->assertInstanceOf(RefundService::class, $this->refundService);
    }

    public function test_validates_refund_amount_not_exceeding_transaction_amount()
    {
        $transaction = new Transaction([
            'amount' => 100.00,
            'status' => 'completed',
        ]);

        $result = $this->refundService->processRefund($transaction, 150.00);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot exceed', $result['message']);
    }

    public function test_validates_refund_amount_greater_than_zero()
    {
        $transaction = new Transaction([
            'amount' => 100.00,
            'status' => 'completed',
        ]);

        $result = $this->refundService->processRefund($transaction, 0);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('greater than zero', $result['message']);
    }

    public function test_validates_transaction_status_for_refund()
    {
        $transaction = new Transaction([
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $result = $this->refundService->processRefund($transaction);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be refunded', $result['message']);
    }

    public function test_can_check_if_transaction_can_be_refunded()
    {
        $transaction = new Transaction([
            'amount' => 100.00,
            'status' => 'completed',
            'refund_amount' => 0,
        ]);

        $this->assertTrue($this->refundService->canRefund($transaction));

        $transaction->status = 'pending';
        $this->assertFalse($this->refundService->canRefund($transaction));

        $transaction->status = 'completed';
        $transaction->refund_amount = 100.00;
        $this->assertFalse($this->refundService->canRefund($transaction));
    }

    public function test_get_refund_details_returns_correct_information()
    {
        $transaction = new Transaction([
            'id' => 1,
            'amount' => 100.00,
            'refund_amount' => 30.00,
            'refund_status' => 'partial',
            'status' => 'completed',
        ]);

        $details = $this->refundService->getRefundDetails($transaction);

        $this->assertEquals(1, $details['transaction_id']);
        $this->assertEquals(100.00, $details['original_amount']);
        $this->assertEquals(30.00, $details['refunded_amount']);
        $this->assertEquals(70.00, $details['remaining_refundable']);
        $this->assertEquals('partial', $details['refund_status']);
        $this->assertTrue($details['can_refund']);
    }
}
