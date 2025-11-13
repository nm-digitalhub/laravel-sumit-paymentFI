<?php

namespace Sumit\LaravelPayment\Filament\Resources\TransactionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
