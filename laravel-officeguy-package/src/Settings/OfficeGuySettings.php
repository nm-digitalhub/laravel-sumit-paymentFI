<?php

namespace NmDigitalHub\LaravelOfficeGuy\Settings;

use Spatie\LaravelSettings\Settings;

class OfficeGuySettings extends Settings
{
    public string $company_id;
    public string $api_private_key;
    public string $api_public_key;
    public string $environment;
    public string $merchant_number;
    public string $subscriptions_merchant_number;
    public bool $testing_mode;
    public bool $authorize_only;
    public bool $auto_capture;
    public bool $draft_document;
    public bool $send_document_by_email;

    public static function group(): string
    {
        return 'officeguy';
    }
}
