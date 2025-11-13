<?php

namespace Sumit\LaravelPayment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sumit\LaravelPayment\Services\ApiService;

class ApiServiceTest extends TestCase
{
    protected ApiService $apiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->apiService = new ApiService(
            'test-company-id',
            'test-api-key',
            'test-public-key',
            'dev'
        );
    }

    public function test_can_instantiate_api_service()
    {
        $this->assertInstanceOf(ApiService::class, $this->apiService);
    }

    public function test_get_url_for_production()
    {
        $apiService = new ApiService('id', 'key', 'pubkey', 'www');
        $reflection = new \ReflectionClass($apiService);
        $method = $reflection->getMethod('getUrl');
        $method->setAccessible(true);
        
        $url = $method->invoke($apiService, '/test');
        
        $this->assertEquals('https://api.sumit.co.il/test', $url);
    }

    public function test_get_url_for_development()
    {
        $apiService = new ApiService('id', 'key', 'pubkey', 'dev');
        $reflection = new \ReflectionClass($apiService);
        $method = $reflection->getMethod('getUrl');
        $method->setAccessible(true);
        
        $url = $method->invoke($apiService, '/test');
        
        $this->assertEquals('http://dev.api.sumit.co.il/test', $url);
    }
}
