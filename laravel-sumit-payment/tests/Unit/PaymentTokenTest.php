<?php

namespace NmDigitalHub\LaravelSumitPayment\Tests\Unit;

use NmDigitalHub\LaravelSumitPayment\Tests\TestCase;
use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTokenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_payment_token()
    {
        $token = PaymentToken::create([
            'user_id' => 1,
            'token' => 'test-token-123',
            'card_type' => 'card',
            'last_four' => '4242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('sumit_payment_tokens', [
            'user_id' => 1,
            'token' => 'test-token-123',
            'last_four' => '4242',
        ]);
    }

    /** @test */
    public function it_returns_masked_card_number()
    {
        $token = PaymentToken::create([
            'user_id' => 1,
            'token' => 'test-token',
            'last_four' => '1234',
            'expiry_month' => '01',
            'expiry_year' => '2025',
        ]);

        $this->assertEquals('****1234', $token->masked_card);
    }

    /** @test */
    public function it_returns_formatted_expiry()
    {
        $token = PaymentToken::create([
            'user_id' => 1,
            'token' => 'test-token',
            'last_four' => '1234',
            'expiry_month' => '03',
            'expiry_year' => '2025',
        ]);

        $this->assertEquals('03/25', $token->expiry);
    }

    /** @test */
    public function it_can_scope_default_tokens()
    {
        PaymentToken::create([
            'user_id' => 1,
            'token' => 'token-1',
            'last_four' => '1111',
            'expiry_month' => '01',
            'expiry_year' => '2025',
            'is_default' => true,
        ]);

        PaymentToken::create([
            'user_id' => 1,
            'token' => 'token-2',
            'last_four' => '2222',
            'expiry_month' => '01',
            'expiry_year' => '2025',
            'is_default' => false,
        ]);

        $defaultTokens = PaymentToken::default()->get();

        $this->assertCount(1, $defaultTokens);
        $this->assertEquals('1111', $defaultTokens->first()->last_four);
    }

    /** @test */
    public function it_can_scope_tokens_by_user()
    {
        PaymentToken::create([
            'user_id' => 1,
            'token' => 'token-1',
            'last_four' => '1111',
            'expiry_month' => '01',
            'expiry_year' => '2025',
        ]);

        PaymentToken::create([
            'user_id' => 2,
            'token' => 'token-2',
            'last_four' => '2222',
            'expiry_month' => '01',
            'expiry_year' => '2025',
        ]);

        $userTokens = PaymentToken::forUser(1)->get();

        $this->assertCount(1, $userTokens);
        $this->assertEquals('1111', $userTokens->first()->last_four);
    }
}
