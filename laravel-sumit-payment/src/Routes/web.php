<?php

use Illuminate\Support\Facades\Route;
use NmDigitalHub\LaravelSumitPayment\Controllers\PaymentController;
use NmDigitalHub\LaravelSumitPayment\Controllers\WebhookController;
use NmDigitalHub\LaravelSumitPayment\Controllers\TokenController;

$prefix = config('sumit-payment.routes.prefix', 'sumit');
$middleware = config('sumit-payment.routes.middleware', ['web']);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    // Webhook endpoints (no auth required)
    Route::post('/webhook/callback', [WebhookController::class, 'handleCallback'])
        ->name('sumit.webhook.callback');
    
    Route::post('/webhook/bit-ipn', [WebhookController::class, 'handleBitIpn'])
        ->name('sumit.webhook.bit-ipn');

    // Payment endpoints (auth required)
    Route::middleware(['auth'])->group(function () {
        Route::post('/payment/process', [PaymentController::class, 'processPayment'])
            ->name('sumit.payment.process');
        
        Route::post('/payment/refund', [PaymentController::class, 'processRefund'])
            ->name('sumit.payment.refund');

        // Token management endpoints
        Route::get('/tokens', [TokenController::class, 'index'])
            ->name('sumit.tokens.index');
        
        Route::post('/tokens', [TokenController::class, 'store'])
            ->name('sumit.tokens.store');
        
        Route::post('/tokens/{tokenId}/set-default', [TokenController::class, 'setDefault'])
            ->name('sumit.tokens.set-default');
        
        Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy'])
            ->name('sumit.tokens.destroy');
    });
});
