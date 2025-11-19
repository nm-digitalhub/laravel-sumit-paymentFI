<?php

namespace Sumit\LaravelPayment\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
