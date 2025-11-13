# Quick Start Guide

This guide will help you get started with the Laravel OfficeGuy package in just a few minutes.

## 1. Install the Package

```bash
composer require nm-digitalhub/laravel-officeguy
```

## 2. Publish Configuration

```bash
php artisan vendor:publish --tag=officeguy-config
php artisan vendor:publish --tag=officeguy-migrations
```

## 3. Run Migrations

```bash
php artisan migrate
```

## 4. Configure Environment

Add to your `.env`:

```env
OFFICEGUY_COMPANY_ID=your_company_id
OFFICEGUY_PRIVATE_KEY=your_private_key
OFFICEGUY_PUBLIC_KEY=your_public_key
OFFICEGUY_ENVIRONMENT=www
OFFICEGUY_MERCHANT_NUMBER=your_merchant_number
```

Get your credentials from: https://app.sumit.co.il/developers/keys/

## 5. Test Your Configuration

```bash
php artisan officeguy:test-credentials
```

You should see:
```
✓ Private API credentials are valid
✓ Public API credentials are valid
All credentials are valid! You can start using the package.
```

## 6. Process Your First Payment

Create a controller:

```php
use NmDigitalHub\LaravelOfficeGuy\Services\PaymentService;

class PaymentController extends Controller
{
    public function processPayment(Request $request, PaymentService $paymentService)
    {
        $result = $paymentService->processPayment([
            'amount' => 100.00,
            'currency' => 'ILS',
            'user_id' => auth()->id(),
            'description' => 'Test Payment',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '0501234567',
            ],
            'single_use_token' => $request->input('payment_token'),
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'payment_id' => $result['payment']->id,
                'transaction_id' => $result['transaction_id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }
}
```

## 7. Set Up Routes

In your `routes/api.php`:

```php
Route::post('/process-payment', [PaymentController::class, 'processPayment']);
```

## 8. Make a Test Payment

Using curl or Postman:

```bash
curl -X POST http://yourapp.test/api/process-payment \
  -H "Content-Type: application/json" \
  -d '{
    "payment_token": "single-use-token-from-js",
    "amount": 100
  }'
```

## Next Steps

- **Set up webhooks**: Configure webhook URL in SUMIT dashboard
- **Add token storage**: Enable users to save payment methods
- **Stock sync**: Configure inventory synchronization
- **Event listeners**: Create custom listeners for payment events
- **Subscriptions**: Set up recurring payments

## Common Issues

**Issue**: "Credentials are invalid"
**Solution**: Double-check your `.env` file and ensure credentials are correct

**Issue**: "Payment failed"
**Solution**: Check logs in `storage/logs/laravel.log` for detailed error messages

**Issue**: "Route not found"
**Solution**: Clear route cache with `php artisan route:clear`

## Additional Resources

- [Full Documentation](README.md)
- [Installation Guide](INSTALLATION.md)
- [API Reference](API.md)
- [Migration from WooCommerce](MIGRATION.md)

## Need Help?

- Email: info@nm-digitalhub.com
- GitHub Issues: https://github.com/nm-digitalhub/laravel-officeguy/issues
- SUMIT Support: https://help.sumit.co.il

---

**Ready to build!** You now have a working payment system integrated with SUMIT. 🎉
