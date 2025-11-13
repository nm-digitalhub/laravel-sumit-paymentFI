<?php

namespace NmDigitalHub\LaravelOfficeGuy;

use Illuminate\Support\ServiceProvider;
use NmDigitalHub\LaravelOfficeGuy\Services\OfficeGuyApiService;
use NmDigitalHub\LaravelOfficeGuy\Services\PaymentService;
use NmDigitalHub\LaravelOfficeGuy\Services\TokenService;
use NmDigitalHub\LaravelOfficeGuy\Services\StockService;
use NmDigitalHub\LaravelOfficeGuy\Services\SubscriptionService;

class OfficeGuyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/officeguy.php',
            'officeguy'
        );

        // Register API Service
        $this->app->singleton(OfficeGuyApiService::class, function ($app) {
            return new OfficeGuyApiService(
                config('officeguy.company_id'),
                config('officeguy.api_private_key'),
                config('officeguy.api_public_key'),
                config('officeguy.environment')
            );
        });

        // Register Payment Service
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(OfficeGuyApiService::class)
            );
        });

        // Register Token Service
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService(
                $app->make(OfficeGuyApiService::class)
            );
        });

        // Register Stock Service
        $this->app->singleton(StockService::class, function ($app) {
            return new StockService(
                $app->make(OfficeGuyApiService::class)
            );
        });

        // Register Subscription Service
        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService(
                $app->make(PaymentService::class),
                $app->make(TokenService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/officeguy.php' => config_path('officeguy.php'),
        ], 'officeguy-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'officeguy-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load views (if needed)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'officeguy');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/officeguy'),
        ], 'officeguy-views');

        // Register event listeners
        $this->registerEventListeners();

        // Register middleware
        $this->registerMiddleware();

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \NmDigitalHub\LaravelOfficeGuy\Console\Commands\SyncStockCommand::class,
                \NmDigitalHub\LaravelOfficeGuy\Console\Commands\TestCredentialsCommand::class,
            ]);
        }
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    protected function registerEventListeners()
    {
        // Register default event listeners
        $events = $this->app['events'];

        $events->listen(
            \NmDigitalHub\LaravelOfficeGuy\Events\PaymentProcessed::class,
            \NmDigitalHub\LaravelOfficeGuy\Listeners\LogSuccessfulPayment::class
        );

        $events->listen(
            \NmDigitalHub\LaravelOfficeGuy\Events\PaymentFailed::class,
            \NmDigitalHub\LaravelOfficeGuy\Listeners\LogFailedPayment::class
        );
    }

    /**
     * Register middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('officeguy.verify', \NmDigitalHub\LaravelOfficeGuy\Middleware\VerifyWebhookSignature::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            OfficeGuyApiService::class,
            PaymentService::class,
            TokenService::class,
            StockService::class,
            SubscriptionService::class,
        ];
    }
}
