<?php

namespace Sumit\LaravelPayment\DTO;

/**
 * Token Data Transfer Object
 * 
 * Represents payment token data in a type-safe manner.
 * This DTO is completely independent of any models.
 */
class TokenData
{
    public function __construct(
        public readonly string $token,
        public readonly string $lastFourDigits,
        public readonly string $expiryMonth,
        public readonly string $expiryYear,
        public readonly ?string $cardType = null,
        public readonly ?string $cardholderName = null,
        public readonly bool $isDefault = false,
        public readonly array $metadata = []
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            lastFourDigits: $data['last_four_digits'] ?? substr($data['card_number'] ?? '', -4),
            expiryMonth: $data['expiry_month'],
            expiryYear: $data['expiry_year'],
            cardType: $data['card_type'] ?? null,
            cardholderName: $data['cardholder_name'] ?? null,
            isDefault: $data['is_default'] ?? false,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        $expiryDate = \DateTime::createFromFormat('Y-m', "20{$this->expiryYear}-{$this->expiryMonth}");
        if (!$expiryDate) {
            return true;
        }

        $expiryDate->modify('last day of this month')->setTime(23, 59, 59);
        return $expiryDate < new \DateTime();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'last_four_digits' => $this->lastFourDigits,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'card_type' => $this->cardType,
            'cardholder_name' => $this->cardholderName,
            'is_default' => $this->isDefault,
            'metadata' => $this->metadata,
        ];
    }
}
