<?php

namespace Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource;

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
