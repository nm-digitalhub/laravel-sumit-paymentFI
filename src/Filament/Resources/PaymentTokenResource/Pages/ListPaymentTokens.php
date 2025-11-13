<?php

namespace Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource;

class ListPaymentTokens extends ListRecords
{
    protected static string $resource = PaymentTokenResource::class;
}
