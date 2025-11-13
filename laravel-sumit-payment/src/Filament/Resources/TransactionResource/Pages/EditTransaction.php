<?php

namespace Sumit\LaravelPayment\Filament\Resources\TransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
