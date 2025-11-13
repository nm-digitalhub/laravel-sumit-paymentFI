<?php

namespace Sumit\LaravelPayment\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource;
use Sumit\LaravelPayment\Filament\Pages\ManagePaymentSettings;

class SumitPaymentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'sumit-payment';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                TransactionResource::class,
                PaymentTokenResource::class,
            ])
            ->pages([
                ManagePaymentSettings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
