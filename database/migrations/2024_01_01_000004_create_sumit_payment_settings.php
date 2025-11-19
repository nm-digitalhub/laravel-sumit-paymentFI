<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateSumitPaymentSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sumit_payment.company_id', env('SUMIT_COMPANY_ID', ''));
        $this->migrator->add('sumit_payment.api_key', env('SUMIT_API_KEY', ''));
        $this->migrator->add('sumit_payment.api_public_key', env('SUMIT_API_PUBLIC_KEY', ''));
        $this->migrator->add('sumit_payment.merchant_number', env('SUMIT_MERCHANT_NUMBER', ''));
        $this->migrator->add('sumit_payment.subscriptions_merchant_number', env('SUMIT_SUBSCRIPTIONS_MERCHANT_NUMBER'));
        $this->migrator->add('sumit_payment.environment', env('SUMIT_ENVIRONMENT', 'www'));
        $this->migrator->add('sumit_payment.testing_mode', env('SUMIT_TESTING_MODE', false));
        $this->migrator->add('sumit_payment.pci_mode', env('SUMIT_PCI_MODE', 'direct'));
        $this->migrator->add('sumit_payment.email_document', env('SUMIT_EMAIL_DOCUMENT', true));
        $this->migrator->add('sumit_payment.document_language', env('SUMIT_DOCUMENT_LANGUAGE', 'he'));
        $this->migrator->add('sumit_payment.maximum_payments', env('SUMIT_MAXIMUM_PAYMENTS', 12));
        $this->migrator->add('sumit_payment.draft_document', env('SUMIT_DRAFT_DOCUMENT', false));
        $this->migrator->add('sumit_payment.authorize_only', env('SUMIT_AUTHORIZE_ONLY', false));
        $this->migrator->add('sumit_payment.auto_capture', env('SUMIT_AUTO_CAPTURE', true));
        $this->migrator->add('sumit_payment.authorize_added_percent', env('SUMIT_AUTHORIZE_ADDED_PERCENT', 0));
        $this->migrator->add('sumit_payment.authorize_minimum_addition', env('SUMIT_AUTHORIZE_MINIMUM_ADDITION', 0));
        $this->migrator->add('sumit_payment.token_method', env('SUMIT_TOKEN_METHOD', 'J2'));
        $this->migrator->add('sumit_payment.api_timeout', env('SUMIT_API_TIMEOUT', 180));
        $this->migrator->add('sumit_payment.send_client_ip', env('SUMIT_SEND_CLIENT_IP', true));
        $this->migrator->add('sumit_payment.vat_included', env('SUMIT_VAT_INCLUDED', true));
        $this->migrator->add('sumit_payment.default_vat_rate', env('SUMIT_DEFAULT_VAT_RATE', 17));
    }
}
