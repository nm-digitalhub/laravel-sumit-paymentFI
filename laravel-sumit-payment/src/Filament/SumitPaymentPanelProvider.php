<?php

namespace Sumit\LaravelPayment\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource;
use Sumit\LaravelPayment\Filament\Pages\ManageSumitPaymentSettings;

class SumitPaymentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sumit-payment')
            ->path('admin/sumit-payment')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Sumit\\LaravelPayment\\Filament\\Resources')
            ->resources([
                TransactionResource::class,
                PaymentTokenResource::class,
                CustomerResource::class,
            ])
            ->pages([
                ManageSumitPaymentSettings::class,
            ])
            ->widgets([]);
    }
}
