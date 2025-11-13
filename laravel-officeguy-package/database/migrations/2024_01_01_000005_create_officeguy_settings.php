<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('officeguy.company_id', '');
        $this->migrator->add('officeguy.api_private_key', '');
        $this->migrator->add('officeguy.api_public_key', '');
        $this->migrator->add('officeguy.environment', 'www');
        $this->migrator->add('officeguy.merchant_number', '');
        $this->migrator->add('officeguy.subscriptions_merchant_number', '');
        $this->migrator->add('officeguy.testing_mode', false);
        $this->migrator->add('officeguy.authorize_only', false);
        $this->migrator->add('officeguy.auto_capture', true);
        $this->migrator->add('officeguy.draft_document', false);
        $this->migrator->add('officeguy.send_document_by_email', true);
    }
};
