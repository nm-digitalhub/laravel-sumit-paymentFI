<?php

namespace Sumit\LaravelPayment\Filament\Resources\TransactionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
