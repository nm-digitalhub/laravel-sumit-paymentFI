<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentResource;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentTokenResource;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\CustomerResource;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\StockSyncLogResource;
use NmDigitalHub\LaravelOfficeGuy\Filament\Pages\ManageOfficeGuySettings;

class OfficeGuyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('officeguy')
            ->path('admin/officeguy')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: __DIR__ . '/Resources', for: 'NmDigitalHub\\LaravelOfficeGuy\\Filament\\Resources')
            ->resources([
                PaymentResource::class,
                PaymentTokenResource::class,
                CustomerResource::class,
                StockSyncLogResource::class,
            ])
            ->pages([
                ManageOfficeGuySettings::class,
            ])
            ->widgets([]);
    }
}
