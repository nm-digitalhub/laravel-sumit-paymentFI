<?php

namespace NmDigitalHub\LaravelSumitPayment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use NmDigitalHub\LaravelSumitPayment\SumitPaymentServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../src/Migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            SumitPaymentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup test configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup package configuration
        $app['config']->set('sumit-payment.company_id', 'test-company');
        $app['config']->set('sumit-payment.api_key', 'test-key');
        $app['config']->set('sumit-payment.environment', 'dev');
        $app['config']->set('sumit-payment.merchant_number', '123456');
    }
}
