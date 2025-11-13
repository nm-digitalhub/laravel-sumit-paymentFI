<?php

namespace NmDigitalHub\LaravelOfficeGuy\Helpers;

class PaymentHelper
{
    /**
     * Format amount for display.
     */
    public static function formatAmount(float $amount, string $currency = 'ILS'): string
    {
        $symbols = [
            'ILS' => '₪',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . number_format($amount, 2);
    }

    /**
     * Get payment status badge color.
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'success', 'captured' => 'green',
            'failed' => 'red',
            'pending' => 'yellow',
            'authorized' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get payment status label.
     */
    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Successful',
            'failed' => 'Failed',
            'pending' => 'Pending',
            'authorized' => 'Authorized',
            'captured' => 'Captured',
            default => ucfirst($status),
        };
    }

    /**
     * Validate credit card number using Luhn algorithm.
     */
    public static function validateCardNumber(string $number): bool
    {
        $number = str_replace([' ', '-'], '', $number);
        
        if (!ctype_digit($number) || strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * Get card brand from number.
     */
    public static function getCardBrand(string $number): string
    {
        $number = str_replace([' ', '-'], '', $number);

        // Visa
        if (preg_match('/^4/', $number)) {
            return 'Visa';
        }

        // Mastercard
        if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7]/', $number)) {
            return 'Mastercard';
        }

        // American Express
        if (preg_match('/^3[47]/', $number)) {
            return 'American Express';
        }

        // Diners Club
        if (preg_match('/^3(?:0[0-5]|[68])/', $number)) {
            return 'Diners Club';
        }

        // Discover
        if (preg_match('/^6(?:011|5)/', $number)) {
            return 'Discover';
        }

        // JCB
        if (preg_match('/^35/', $number)) {
            return 'JCB';
        }

        return 'Unknown';
    }

    /**
     * Mask card number for display.
     */
    public static function maskCardNumber(string $number, string $mask = '*'): string
    {
        $number = str_replace([' ', '-'], '', $number);
        $last4 = substr($number, -4);
        $masked = str_repeat($mask, strlen($number) - 4);
        
        return $masked . $last4;
    }

    /**
     * Format card number with spaces.
     */
    public static function formatCardNumber(string $number): string
    {
        $number = str_replace([' ', '-'], '', $number);
        return chunk_split($number, 4, ' ');
    }

    /**
     * Validate expiry date.
     */
    public static function validateExpiryDate(int $month, int $year): bool
    {
        if ($month < 1 || $month > 12) {
            return false;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        // Normalize to 4-digit year
        if ($year < 100) {
            $year += 2000;
        }

        if ($year < $currentYear || $year > $currentYear + 20) {
            return false;
        }

        if ($year === $currentYear && $month < $currentMonth) {
            return false;
        }

        return true;
    }

    /**
     * Check if card is expired.
     */
    public static function isCardExpired(int $month, int $year): bool
    {
        // Normalize to 4-digit year
        if ($year < 100) {
            $year += 2000;
        }

        $expiryDate = sprintf('%04d-%02d-01', $year, $month);
        $expiry = \Carbon\Carbon::parse($expiryDate)->endOfMonth();
        
        return $expiry->isPast();
    }

    /**
     * Calculate installment amount.
     */
    public static function calculateInstallmentAmount(float $totalAmount, int $installments): float
    {
        if ($installments <= 0) {
            return $totalAmount;
        }

        return round($totalAmount / $installments, 2);
    }

    /**
     * Get allowed payment methods based on amount.
     */
    public static function getAllowedPayments(float $amount): int
    {
        $maxPayments = config('officeguy.payment_limits.max_payments', 1);
        $minAmountForPayments = config('officeguy.payment_limits.min_amount_for_payments', 0);
        $minAmountPerPayment = config('officeguy.payment_limits.min_amount_per_payment', 0);

        if ($amount < $minAmountForPayments) {
            return 1;
        }

        if ($minAmountPerPayment > 0) {
            $calculatedMax = floor($amount / $minAmountPerPayment);
            return min($maxPayments, (int) $calculatedMax);
        }

        return $maxPayments;
    }
}
