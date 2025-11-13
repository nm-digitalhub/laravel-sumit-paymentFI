<?php

use Illuminate\Support\Facades\Route;
use Sumit\LaravelPayment\Controllers\PaymentController;
use Sumit\LaravelPayment\Controllers\TokenController;
use Sumit\LaravelPayment\Controllers\WebhookController;

// Get route configuration
$prefix = config('sumit-payment.routes.prefix', 'sumit');
$middleware = config('sumit-payment.routes.middleware', ['web']);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    // Payment routes
    Route::post('/payment/process', [PaymentController::class, 'processPayment'])
        ->name('sumit.payment.process');
    
    Route::get('/payment/callback', [PaymentController::class, 'handleCallback'])
        ->name('sumit.payment.callback');
    
    Route::get('/payment/{transactionId}', [PaymentController::class, 'getTransaction'])
        ->name('sumit.payment.show');

    // Webhook routes (no auth middleware - external service)
    Route::post('/webhook', [WebhookController::class, 'handle'])
        ->name('sumit.webhook.handle')
        ->withoutMiddleware('web');

    // Token management routes (requires authentication)
    Route::middleware(['auth'])->group(function () {
        Route::get('/tokens', [TokenController::class, 'index'])
            ->name('sumit.tokens.index');
        
        Route::post('/tokens', [TokenController::class, 'store'])
            ->name('sumit.tokens.store');
        
        Route::put('/tokens/{tokenId}/default', [TokenController::class, 'setDefault'])
            ->name('sumit.tokens.default');
        
        Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy'])
            ->name('sumit.tokens.destroy');
    });
});
