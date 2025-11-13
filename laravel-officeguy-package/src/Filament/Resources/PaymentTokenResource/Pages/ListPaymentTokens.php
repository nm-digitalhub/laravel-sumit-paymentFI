<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentTokenResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentTokenResource;

class ListPaymentTokens extends ListRecords
{
    protected static string $resource = PaymentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
