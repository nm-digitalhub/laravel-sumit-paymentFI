# Quick Start Guide

Get up and running with Laravel SUMIT Payment in 5 minutes!

## Step 1: Install Package

```bash
composer require nm-digitalhub/laravel-sumit-payment
```

## Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=sumit-payment-config
```

## Step 3: Configure Environment

Add to your `.env` file:

```env
SUMIT_COMPANY_ID=your_company_id_here
SUMIT_API_KEY=your_api_key_here
SUMIT_MERCHANT_NUMBER=your_merchant_number
SUMIT_ENVIRONMENT=www
```

## Step 4: Run Migrations

```bash
php artisan migrate
```

## Step 5: Process Your First Payment

```php
use NmDigitalHub\LaravelSumitPayment\Facades\SumitPayment;

// In your controller
public function checkout(Request $request)
{
    $result = SumitPayment::processPayment([
        'order_id' => 'ORDER-' . uniqid(),
        'user_id' => auth()->id(),
        'amount' => 99.99,
        'currency' => 'ILS',
        'items' => [
            [
                'Item' => [
                    'Name' => 'Product Name',
                    'SearchMode' => 'Automatic',
                ],
                'Quantity' => 1,
                'UnitPrice' => 99.99,
                'Currency' => 'ILS',
            ],
        ],
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0501234567',
        ],
    ]);

    if ($result['success']) {
        return redirect()->route('payment.success')
            ->with('transaction_id', $result['transaction']->id);
    }

    return back()->withErrors(['payment' => $result['error']]);
}
```

## That's It! 🎉

Your Laravel application is now ready to process payments through SUMIT.

## Next Steps

- 📖 Read the [full documentation](README.md)
- 🔧 Check out [usage examples](EXAMPLES.md)
- 🎣 Learn about [custom hooks](HOOKS.md)
- 🔄 Migrating from WooCommerce? See the [migration guide](MIGRATION.md)

## Common Configuration Options

### Enable Installments

```env
SUMIT_MAX_PAYMENTS=12
SUMIT_MIN_AMOUNT_FOR_PAYMENTS=100
```

### Enable Email Invoices

```env
SUMIT_EMAIL_DOCUMENT=true
```

### Enable Token Storage

```env
SUMIT_SUPPORT_TOKENS=true
SUMIT_TOKEN_PARAM=J5
```

### Enable Testing Mode

```env
SUMIT_TESTING_MODE=true
SUMIT_LOGGING=true
```

## Testing Your Integration

Use SUMIT's test card:

```
Card Number: 4580000000000000
CVV: 123
Expiry: Any future date
```

## Getting Help

- 📚 Check the [README](README.md)
- 💡 Browse [examples](EXAMPLES.md)
- 🐛 [Report issues](https://github.com/nm-digitalhub/laravel-sumit-payment/issues)
- 📧 Contact SUMIT support: support@sumit.co.il

## API Documentation

SUMIT API docs: https://help.sumit.co.il

---

**Happy coding!** 🚀
