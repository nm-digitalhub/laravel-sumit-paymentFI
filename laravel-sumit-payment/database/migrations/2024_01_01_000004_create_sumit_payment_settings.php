<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateSumitPaymentSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sumit_payment.company_id', config('sumit-payment.company_id', ''));
        $this->migrator->add('sumit_payment.api_key', config('sumit-payment.api_key', ''));
        $this->migrator->add('sumit_payment.api_public_key', config('sumit-payment.api_public_key', ''));
        $this->migrator->add('sumit_payment.merchant_number', config('sumit-payment.merchant_number', ''));
        $this->migrator->add('sumit_payment.subscriptions_merchant_number', config('sumit-payment.subscriptions_merchant_number'));
        $this->migrator->add('sumit_payment.environment', config('sumit-payment.environment', 'www'));
        $this->migrator->add('sumit_payment.testing_mode', config('sumit-payment.testing_mode', false));
        $this->migrator->add('sumit_payment.pci_mode', config('sumit-payment.pci_mode', 'direct'));
        $this->migrator->add('sumit_payment.email_document', config('sumit-payment.email_document', true));
        $this->migrator->add('sumit_payment.document_language', config('sumit-payment.document_language', 'he'));
        $this->migrator->add('sumit_payment.maximum_payments', config('sumit-payment.maximum_payments', 12));
        $this->migrator->add('sumit_payment.draft_document', config('sumit-payment.draft_document', false));
        $this->migrator->add('sumit_payment.authorize_only', config('sumit-payment.authorize_only', false));
        $this->migrator->add('sumit_payment.auto_capture', config('sumit-payment.auto_capture', true));
        $this->migrator->add('sumit_payment.authorize_added_percent', config('sumit-payment.authorize_added_percent', 0));
        $this->migrator->add('sumit_payment.authorize_minimum_addition', config('sumit-payment.authorize_minimum_addition', 0));
        $this->migrator->add('sumit_payment.token_method', config('sumit-payment.token_method', 'J2'));
        $this->migrator->add('sumit_payment.api_timeout', config('sumit-payment.api_timeout', 180));
        $this->migrator->add('sumit_payment.send_client_ip', config('sumit-payment.send_client_ip', true));
        $this->migrator->add('sumit_payment.vat_included', config('sumit-payment.vat_included', true));
        $this->migrator->add('sumit_payment.default_vat_rate', config('sumit-payment.default_vat_rate', 17));
    }
}
