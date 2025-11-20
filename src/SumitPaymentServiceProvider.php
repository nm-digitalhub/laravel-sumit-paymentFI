<?php

namespace Sumit\LaravelPayment;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Sumit\LaravelPayment\Services\PaymentService;
use Sumit\LaravelPayment\Services\ApiService;
use Sumit\LaravelPayment\Services\TokenService;
use Sumit\LaravelPayment\Settings\PaymentSettings;

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
        $this->app->bind(ApiService::class, function ($app) {
            return new ApiService(
                $app->make(PaymentSettings::class)
            );
        });

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService();
        });

        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(ApiService::class),
                $app->make(TokenService::class),
                $app->make(PaymentSettings::class)
            );
        });

        $this->app->bind(\Sumit\LaravelPayment\Services\RefundService::class, function ($app) {
            return new \Sumit\LaravelPayment\Services\RefundService(
                $app->make(ApiService::class),
                $app->make(PaymentSettings::class)
            );
        });

        $this->app->bind(\Sumit\LaravelPayment\Services\RecurringBillingService::class, function ($app) {
            return new \Sumit\LaravelPayment\Services\RecurringBillingService(
                $app->make(PaymentService::class),
                $app->make(TokenService::class)
            );
        });

        // Alias for facade
        $this->app->alias(PaymentService::class, 'sumit-payment');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/sumit-payment.php' => config_path('sumit-payment.php'),
        ], 'sumit-payment-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'sumit-payment-migrations');

        // Load migrations (only if models are enabled)
        if ($this->shouldLoadMigrations()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sumit-payment');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sumit-payment'),
        ], 'sumit-payment-views');

        // Register optional model listeners
        $this->registerModelListeners();
    }

    /**
     * Register optional model listeners
     * 
     * These listeners are only registered if the corresponding models are enabled.
     * Users can choose to disable models and use their own storage implementation.
     */
    protected function registerModelListeners(): void
    {
        // Only register if models are enabled
        if (!$this->areModelsEnabled()) {
            return;
        }

        Event::listen(
            \Sumit\LaravelPayment\Events\PaymentCreated::class,
            \Sumit\LaravelPayment\Listeners\ModelListeners\StorePaymentInDatabase::class
        );

        Event::listen(
            \Sumit\LaravelPayment\Events\PaymentCompleted::class,
            \Sumit\LaravelPayment\Listeners\ModelListeners\UpdatePaymentStatus::class
        );

        Event::listen(
            \Sumit\LaravelPayment\Events\PaymentFailed::class,
            \Sumit\LaravelPayment\Listeners\ModelListeners\MarkPaymentAsFailed::class
        );

        Event::listen(
            \Sumit\LaravelPayment\Events\TokenCreated::class,
            \Sumit\LaravelPayment\Listeners\ModelListeners\StoreTokenInDatabase::class
        );

        Event::listen(
            \Sumit\LaravelPayment\Events\PaymentRefunded::class,
            \Sumit\LaravelPayment\Listeners\ModelListeners\RecordRefund::class
        );
    }

    /**
     * Check if models are enabled in configuration
     */
    protected function areModelsEnabled(): bool
    {
        return config('sumit-payment.models.transaction') !== null
            || config('sumit-payment.models.token') !== null
            || config('sumit-payment.models.customer') !== null;
    }

    /**
     * Check if migrations should be loaded
     */
    protected function shouldLoadMigrations(): bool
    {
        return $this->areModelsEnabled();
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
            \Sumit\LaravelPayment\Services\RefundService::class,
            \Sumit\LaravelPayment\Services\RecurringBillingService::class,
            'sumit-payment',
        ];
    }
}
