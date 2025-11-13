<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentTokenResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentTokenResource;

class EditPaymentToken extends EditRecord
{
    protected static string $resource = PaymentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
