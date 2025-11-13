<?php

namespace Sumit\LaravelPayment\Filament\Resources\TransactionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Transactions are typically created programmatically, not manually
        ];
    }
}
