<?php

namespace Sumit\LaravelPayment\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public string $company_id;
    public string $api_key;
    public string $api_public_key;
    public string $merchant_number;
    public ?string $subscriptions_merchant_number;
    public string $environment;
    public bool $testing_mode;
    public string $pci_mode;
    public bool $email_document;
    public string $document_language;
    public int $maximum_payments;
    public bool $draft_document;
    public bool $authorize_only;
    public bool $auto_capture;
    public int $authorize_added_percent;
    public int $authorize_minimum_addition;
    public string $token_method;
    public int $api_timeout;
    public bool $send_client_ip;
    public bool $vat_included;
    public int $default_vat_rate;

    public static function group(): string
    {
        return 'sumit_payment';
    }
}
