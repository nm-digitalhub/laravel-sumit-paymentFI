<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Payments are typically created programmatically, not manually
        ];
    }
}
