<?php

namespace Sumit\LaravelPayment\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
