<?php

namespace Sumit\LaravelPayment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sumit\LaravelPayment\Settings\PaymentSettings;

class PaymentSettingsTest extends TestCase
{
    public function test_payment_settings_has_correct_group()
    {
        $this->assertEquals('sumit_payment', PaymentSettings::group());
    }

    public function test_payment_settings_properties_exist()
    {
        $reflection = new \ReflectionClass(PaymentSettings::class);
        
        $expectedProperties = [
            'company_id',
            'api_key',
            'api_public_key',
            'merchant_number',
            'subscriptions_merchant_number',
            'environment',
            'testing_mode',
            'pci_mode',
            'email_document',
            'document_language',
            'maximum_payments',
            'draft_document',
            'authorize_only',
            'auto_capture',
            'authorize_added_percent',
            'authorize_minimum_addition',
            'token_method',
            'api_timeout',
            'send_client_ip',
            'vat_included',
            'default_vat_rate',
        ];

        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                $reflection->hasProperty($property),
                "Property {$property} does not exist in PaymentSettings"
            );
        }
    }
}
