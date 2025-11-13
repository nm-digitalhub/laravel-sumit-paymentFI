<?php

namespace Sumit\LaravelPayment\Settings;

use Spatie\LaravelSettings\Settings;

class SumitPaymentSettings extends Settings
{
    public string $company_id;
    public string $api_key;
    public string $api_public_key;
    public string $environment;
    public bool $testing_mode;
    public string $merchant_number;
    public string $subscriptions_merchant_number;
    public bool $email_document;
    public string $document_language;
    public int $maximum_payments;

    public static function group(): string
    {
        return 'sumit_payment';
    }
}
