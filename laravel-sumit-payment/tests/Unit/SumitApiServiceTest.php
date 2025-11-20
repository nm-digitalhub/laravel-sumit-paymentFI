<?php

namespace NmDigitalHub\LaravelSumitPayment\Tests\Unit;

use NmDigitalHub\LaravelSumitPayment\Tests\TestCase;
use NmDigitalHub\LaravelSumitPayment\Services\SumitApiService;

class SumitApiServiceTest extends TestCase
{
    /** @test */
    public function it_constructs_correct_base_url_for_production()
    {
        $service = new SumitApiService('test-company', 'test-key', 'www');
        
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);
        
        $this->assertEquals('https://api.sumit.co.il', $property->getValue($service));
    }

    /** @test */
    public function it_constructs_correct_base_url_for_development()
    {
        $service = new SumitApiService('test-company', 'test-key', 'dev');
        
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);
        
        $this->assertEquals('http://dev.api.sumit.co.il', $property->getValue($service));
    }

    /** @test */
    public function it_includes_credentials_in_request()
    {
        $service = new SumitApiService('test-company-123', 'test-key-456', 'www');
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getCredentials');
        $method->setAccessible(true);
        
        $credentials = $method->invoke($service);
        
        $this->assertEquals('test-company-123', $credentials['CompanyID']);
        $this->assertEquals('test-key-456', $credentials['APIKey']);
    }
}
