<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sumit_payment.company_id', '');
        $this->migrator->add('sumit_payment.api_key', '');
        $this->migrator->add('sumit_payment.api_public_key', '');
        $this->migrator->add('sumit_payment.environment', 'www');
        $this->migrator->add('sumit_payment.testing_mode', false);
        $this->migrator->add('sumit_payment.merchant_number', '');
        $this->migrator->add('sumit_payment.subscriptions_merchant_number', '');
        $this->migrator->add('sumit_payment.email_document', true);
        $this->migrator->add('sumit_payment.document_language', 'he');
        $this->migrator->add('sumit_payment.maximum_payments', 12);
    }
};
