<?php

namespace Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource;

class CreatePaymentToken extends CreateRecord
{
    protected static string $resource = PaymentTokenResource::class;
}
