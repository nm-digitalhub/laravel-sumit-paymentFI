<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources\StockSyncLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\StockSyncLogResource;

class ListStockSyncLogs extends ListRecords
{
    protected static string $resource = StockSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Stock sync logs are created automatically
        ];
    }
}
