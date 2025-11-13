<?php

use Illuminate\Support\Facades\Route;
use NmDigitalHub\LaravelOfficeGuy\Controllers\PaymentController;
use NmDigitalHub\LaravelOfficeGuy\Controllers\WebhookController;
use NmDigitalHub\LaravelOfficeGuy\Controllers\TokenController;
use NmDigitalHub\LaravelOfficeGuy\Controllers\StockController;

// Get route configuration
$prefix = config('officeguy.routes.prefix', 'officeguy');
$middleware = config('officeguy.routes.middleware', ['api']);
$webhookMiddleware = config('officeguy.routes.webhook_middleware', ['api']);

// Payment routes
Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        // Payment processing
        Route::post('/payments', [PaymentController::class, 'process'])->name('officeguy.payments.process');
        Route::get('/payments', [PaymentController::class, 'index'])->name('officeguy.payments.index');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('officeguy.payments.show');
        Route::post('/payments/{id}/refund', [PaymentController::class, 'refund'])->name('officeguy.payments.refund');

        // Token management
        Route::post('/tokens', [TokenController::class, 'store'])->name('officeguy.tokens.store');
        Route::get('/tokens', [TokenController::class, 'index'])->name('officeguy.tokens.index');
        Route::delete('/tokens/{id}', [TokenController::class, 'destroy'])->name('officeguy.tokens.destroy');
        Route::post('/tokens/{id}/set-default', [TokenController::class, 'setDefault'])->name('officeguy.tokens.set-default');

        // Stock synchronization
        Route::post('/stock/sync', [StockController::class, 'sync'])->name('officeguy.stock.sync');
        Route::get('/stock/logs', [StockController::class, 'logs'])->name('officeguy.stock.logs');
        Route::get('/stock/status', [StockController::class, 'status'])->name('officeguy.stock.status');
    });

// Webhook routes (separate middleware)
Route::prefix($prefix)
    ->middleware($webhookMiddleware)
    ->group(function () {
        Route::post('/webhook', [WebhookController::class, 'handle'])->name('officeguy.webhook');
        Route::get('/redirect', [WebhookController::class, 'redirect'])->name('officeguy.redirect');
    });
