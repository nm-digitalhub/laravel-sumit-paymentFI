<?php

namespace NmDigitalHub\LaravelSumitPayment\Tests\Feature;

use NmDigitalHub\LaravelSumitPayment\Tests\TestCase;
use NmDigitalHub\LaravelSumitPayment\Services\TokenService;
use NmDigitalHub\LaravelSumitPayment\Models\PaymentToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_token()
    {
        $tokenService = app(TokenService::class);

        $token = $tokenService->createToken([
            'user_id' => 1,
            'token' => 'test-token-abc',
            'last_four' => '1234',
            'expiry_month' => '12',
            'expiry_year' => '2025',
        ]);

        $this->assertInstanceOf(PaymentToken::class, $token);
        $this->assertEquals('1234', $token->last_four);
        $this->assertDatabaseHas('sumit_payment_tokens', [
            'token' => 'test-token-abc',
        ]);
    }

    /** @test */
    public function it_sets_default_token_and_unsets_others()
    {
        $tokenService = app(TokenService::class);

        $token1 = $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-1',
            'last_four' => '1111',
            'expiry_month' => '01',
            'expiry_year' => '2025',
            'is_default' => true,
        ]);

        $this->assertTrue($token1->fresh()->is_default);

        $token2 = $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-2',
            'last_four' => '2222',
            'expiry_month' => '02',
            'expiry_year' => '2025',
            'is_default' => true,
        ]);

        $this->assertFalse($token1->fresh()->is_default);
        $this->assertTrue($token2->fresh()->is_default);
    }

    /** @test */
    public function it_gets_default_token_for_user()
    {
        $tokenService = app(TokenService::class);

        $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-1',
            'last_four' => '1111',
            'expiry_month' => '01',
            'expiry_year' => '2025',
            'is_default' => false,
        ]);

        $defaultToken = $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-2',
            'last_four' => '2222',
            'expiry_month' => '02',
            'expiry_year' => '2025',
            'is_default' => true,
        ]);

        $retrieved = $tokenService->getDefaultToken(1);

        $this->assertEquals($defaultToken->id, $retrieved->id);
        $this->assertEquals('2222', $retrieved->last_four);
    }

    /** @test */
    public function it_gets_all_user_tokens()
    {
        $tokenService = app(TokenService::class);

        $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-1',
            'last_four' => '1111',
            'expiry_month' => '01',
            'expiry_year' => '2025',
        ]);

        $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-2',
            'last_four' => '2222',
            'expiry_month' => '02',
            'expiry_year' => '2025',
        ]);

        $tokenService->createToken([
            'user_id' => 2,
            'token' => 'token-3',
            'last_four' => '3333',
            'expiry_month' => '03',
            'expiry_year' => '2025',
        ]);

        $userTokens = $tokenService->getUserTokens(1);

        $this->assertCount(2, $userTokens);
    }

    /** @test */
    public function it_can_delete_a_token()
    {
        $tokenService = app(TokenService::class);

        $token = $tokenService->createToken([
            'user_id' => 1,
            'token' => 'token-to-delete',
            'last_four' => '9999',
            'expiry_month' => '12',
            'expiry_year' => '2025',
        ]);

        $result = $tokenService->deleteToken($token->id, 1);

        $this->assertTrue($result);
        $this->assertSoftDeleted('sumit_payment_tokens', [
            'id' => $token->id,
        ]);
    }
}
