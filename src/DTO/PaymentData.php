<?php

namespace Sumit\LaravelPayment\DTO;

/**
 * Payment Data Transfer Object
 * 
 * Represents payment request data in a type-safe manner.
 * This DTO is completely independent of any models.
 */
class PaymentData
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'ILS',
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $customerAddress = null,
        public readonly ?string $customerCity = null,
        public readonly ?string $customerCountry = null,
        public readonly ?string $customerZip = null,
        public readonly ?string $cardNumber = null,
        public readonly ?string $expiryMonth = null,
        public readonly ?string $expiryYear = null,
        public readonly ?string $cvv = null,
        public readonly ?string $token = null,
        public readonly int $paymentsCount = 1,
        public readonly ?string $language = null,
        public readonly bool $isDonation = false,
        public readonly bool $isSubscription = false,
        public readonly ?string $itemName = null,
        public readonly ?array $items = null,
        public readonly array $metadata = []
    ) {}

    /**
     * Create from array (for backward compatibility)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: $data['amount'],
            currency: $data['currency'] ?? 'ILS',
            description: $data['description'] ?? null,
            customerName: $data['customer_name'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            customerAddress: $data['customer_address'] ?? null,
            customerCity: $data['customer_city'] ?? null,
            customerCountry: $data['customer_country'] ?? null,
            customerZip: $data['customer_zip'] ?? null,
            cardNumber: $data['card_number'] ?? null,
            expiryMonth: $data['expiry_month'] ?? null,
            expiryYear: $data['expiry_year'] ?? null,
            cvv: $data['cvv'] ?? null,
            token: $data['token'] ?? null,
            paymentsCount: $data['payments_count'] ?? 1,
            language: $data['language'] ?? null,
            isDonation: $data['is_donation'] ?? false,
            isSubscription: $data['is_subscription'] ?? false,
            itemName: $data['item_name'] ?? null,
            items: $data['items'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'customer_city' => $this->customerCity,
            'customer_country' => $this->customerCountry,
            'customer_zip' => $this->customerZip,
            'card_number' => $this->cardNumber,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'cvv' => $this->cvv,
            'token' => $this->token,
            'payments_count' => $this->paymentsCount,
            'language' => $this->language,
            'is_donation' => $this->isDonation,
            'is_subscription' => $this->isSubscription,
            'item_name' => $this->itemName,
            'items' => $this->items,
            'metadata' => $this->metadata,
        ];
    }
}
