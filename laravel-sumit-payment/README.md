# Laravel SUMIT Payment Gateway

A comprehensive Laravel package for integrating SUMIT Israeli payment gateway. This package provides a clean, Laravel-native way to process credit card payments, manage payment tokens, and generate invoices/receipts.

## Features

- 💳 **Credit Card Processing** - Secure payment processing with PCI compliance options
- 🔒 **Token Management** - Store and manage payment methods securely
- 📄 **Invoice Generation** - Automatic invoice/receipt creation
- 🔄 **Recurring Payments** - Support for subscription-based payments
- 💰 **Installments** - Configurable installment payments
- 🔁 **Refunds** - Process full and partial refunds
- 🌍 **Multi-Currency** - Support for 35+ currencies
- 🎯 **Events** - Laravel events for payment lifecycle hooks
- 📊 **Database Storage** - Track all transactions and documents
- 🔐 **Secure** - PCI-compliant with multiple security options

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0 or higher
- MySQL/PostgreSQL database

## Installation

Install the package via Composer:

```bash
composer require nm-digitalhub/laravel-sumit-payment
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add the following to your `.env` file:

```env
SUMIT_COMPANY_ID=your_company_id
SUMIT_API_KEY=your_api_key
SUMIT_API_PUBLIC_KEY=your_public_key
SUMIT_ENVIRONMENT=www
SUMIT_MERCHANT_NUMBER=your_merchant_number
```

See `config/sumit-payment.php` for all available configuration options.

## Basic Usage

### Processing a Payment

```php
use NmDigitalHub\LaravelSumitPayment\Facades\SumitPayment;

$result = SumitPayment::processPayment([
    'order_id' => '12345',
    'user_id' => auth()->id(),
    'amount' => 100.00,
    'currency' => 'ILS',
    'items' => [
        [
            'Item' => [
                'Name' => 'Product Name',
                'SearchMode' => 'Automatic',
            ],
            'Quantity' => 1,
            'UnitPrice' => 100.00,
            'Currency' => 'ILS',
        ],
    ],
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '0501234567',
        'address' => '123 Main St',
        'city' => 'Tel Aviv',
    ],
]);

if ($result['success']) {
    // Payment successful
    $transactionId = $result['transaction']->id;
    $paymentId = $result['payment_id'];
    $documentId = $result['document_id'];
}
```

### Using Payment Tokens

```php
use NmDigitalHub\LaravelSumitPayment\Services\TokenService;

$tokenService = app(TokenService::class);

// Generate a token from card details
$result = $tokenService->generateToken([
    'card_number' => '4580000000000000',
    'cvv' => '123',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'citizen_id' => '123456789',
    'is_default' => true,
], auth()->id());

// Pay with saved token
$result = SumitPayment::processPayment([
    // ... other payment data
    'payment_token_id' => $token->id,
]);
```

### Processing Refunds

```php
$result = SumitPayment::processRefund(
    orderId: '12345',
    amount: 50.00,
    description: 'Partial refund'
);
```

### Creating Invoices

```php
use NmDigitalHub\LaravelSumitPayment\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

$result = $invoiceService->createInvoice([
    'order_id' => '12345',
    'type' => 'invoice',
    'currency' => 'ILS',
    'total_amount' => 100.00,
    'items' => [/* ... */],
    'customer' => [/* ... */],
]);
```

## Events

The package dispatches the following events:

- `PaymentProcessing` - Before payment processing starts
- `PaymentCompleted` - After successful payment
- `PaymentFailed` - When payment fails
- `InvoiceCreated` - After invoice creation
- `TokenCreated` - After payment token creation

### Listening to Events

```php
use NmDigitalHub\LaravelSumitPayment\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function ($event) {
    $transaction = $event->transaction;
    $response = $event->response;
    
    // Your custom logic here
});
```

## Custom Hooks

The package supports custom hooks similar to WooCommerce filters:

### Maximum Installments Hook

```php
// In your EventServiceProvider or a listener
use NmDigitalHub\LaravelSumitPayment\Events\PaymentProcessing;

Event::listen(PaymentProcessing::class, function ($event) {
    // Modify payment data before processing
    // Custom logic to adjust maximum installments
});
```

### Custom Customer Fields Hook

You can extend customer data by listening to events and modifying the data structure.

### Custom Item Fields Hook

Similar to customer fields, you can modify item data through event listeners.

## Database Structure

The package creates four tables:

- `sumit_payment_tokens` - Stored payment methods
- `sumit_transactions` - Payment transactions
- `sumit_customers` - Customer records
- `sumit_documents` - Invoices and receipts

## API Endpoints

If webhooks are enabled, the following routes are registered:

- `POST /sumit/webhook/callback` - Payment callback
- `POST /sumit/webhook/bit-ipn` - Bit payment IPN
- `POST /sumit/payment/process` - Process payment (auth required)
- `POST /sumit/payment/refund` - Process refund (auth required)
- `GET /sumit/tokens` - List tokens (auth required)
- `POST /sumit/tokens` - Create token (auth required)
- `DELETE /sumit/tokens/{id}` - Delete token (auth required)

## Testing

The package includes comprehensive tests. Run them with:

```bash
vendor/bin/phpunit
```

## Security

- All sensitive card data is handled according to PCI compliance standards
- Payment tokens are securely stored
- API credentials are encrypted in transit
- Soft deletes preserve data integrity
- Client IP tracking prevents fraud

## Migration from WooCommerce

This package is a direct Laravel implementation of the WooCommerce SUMIT Payment Gateway plugin. Key differences:

1. **Service Providers** instead of WordPress hooks
2. **Eloquent Models** instead of WordPress post meta
3. **Laravel Events** instead of WordPress actions/filters
4. **Database migrations** instead of WordPress custom tables
5. **RESTful Controllers** instead of WordPress admin pages

## Support

For issues, please use the [GitHub issue tracker](https://github.com/nm-digitalhub/laravel-sumit-payment/issues).

For SUMIT API documentation, visit: https://help.sumit.co.il

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- Original WooCommerce Plugin: SUMIT
- Laravel Package: NM DigitalHub

## Changelog

### Version 1.0.0 (2024-01-01)

- Initial release
- Full migration from WooCommerce plugin to Laravel package
- Support for Laravel 11 and 12
- Complete payment processing functionality
- Token management
- Invoice generation
- Event system
- Database migrations
