<?php

namespace Sumit\LaravelPayment;

use Illuminate\Support\ServiceProvider;
use Sumit\LaravelPayment\Services\PaymentService;
use Sumit\LaravelPayment\Services\ApiService;
use Sumit\LaravelPayment\Services\TokenService;
use Sumit\LaravelPayment\Filament\SumitPaymentPanelProvider;

class SumitPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/sumit-payment.php', 'sumit-payment'
        );

        // Register services
        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService(
                config('sumit-payment.company_id'),
                config('sumit-payment.api_key'),
                config('sumit-payment.api_public_key'),
                config('sumit-payment.environment')
            );
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(ApiService::class),
                $app->make(TokenService::class)
            );
        });

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService();
        });

        // Alias for facade
        $this->app->alias(PaymentService::class, 'sumit-payment');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Filament Panel Provider
        if (class_exists(\Filament\Panel::class)) {
            $this->app->register(SumitPaymentPanelProvider::class);
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/sumit-payment.php' => config_path('sumit-payment.php'),
        ], 'sumit-payment-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'sumit-payment-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sumit-payment');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sumit-payment'),
        ], 'sumit-payment-views');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ApiService::class,
            PaymentService::class,
            TokenService::class,
            'sumit-payment',
        ];
    }
}
