<?php

namespace NmDigitalHub\LaravelSumitPayment;

use Illuminate\Support\ServiceProvider;
use NmDigitalHub\LaravelSumitPayment\Services\SumitApiService;
use NmDigitalHub\LaravelSumitPayment\Services\PaymentService;
use NmDigitalHub\LaravelSumitPayment\Services\TokenService;
use NmDigitalHub\LaravelSumitPayment\Services\InvoiceService;

class SumitPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/Config/sumit-payment.php', 'sumit-payment'
        );

        // Register services
        $this->app->singleton(SumitApiService::class, function ($app) {
            return new SumitApiService(
                config('sumit-payment.company_id'),
                config('sumit-payment.api_key'),
                config('sumit-payment.environment')
            );
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(SumitApiService::class),
                $app->make(TokenService::class),
                $app->make(InvoiceService::class)
            );
        });

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService($app->make(SumitApiService::class));
        });

        $this->app->singleton(InvoiceService::class, function ($app) {
            return new InvoiceService($app->make(SumitApiService::class));
        });

        // Register facade
        $this->app->bind('sumit-payment', function ($app) {
            return $app->make(PaymentService::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/Config/sumit-payment.php' => config_path('sumit-payment.php'),
        ], 'sumit-payment-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        // Load routes
        if (config('sumit-payment.webhook_enabled')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // You can register event listeners here or use EventServiceProvider
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SumitApiService::class,
            PaymentService::class,
            TokenService::class,
            InvoiceService::class,
            'sumit-payment',
        ];
    }
}
