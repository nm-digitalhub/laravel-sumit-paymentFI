# Filament Integration Guide

This guide explains how to integrate the SUMIT Payment Gateway with Filament Admin Panel.

## Installation

1. **Install Filament** (if not already installed):

```bash
composer require filament/filament:"^4.1"
```

2. **Register the Plugin** in your Filament Panel Provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Sumit\LaravelPayment\Filament\SumitPaymentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... other configuration
        ->plugins([
            SumitPaymentPlugin::make(),
        ]);
}
```

## Features

### 1. Payment Settings Page

The plugin provides a comprehensive settings page for managing your SUMIT payment gateway configuration:

- **API Credentials**: Company ID, API Key, API Public Key, Merchant Numbers
- **Environment Settings**: Production/Development environment, Testing mode, PCI compliance mode
- **Payment Settings**: Maximum installments, Token method, API timeout
- **Document Settings**: Invoice/receipt generation and language
- **Authorization Settings**: J5 authorization mode configuration
- **VAT Settings**: VAT calculation settings

Access via: **Admin Panel → Payment Gateway → Payment Settings**

### 2. Transaction Resource

View and manage all payment transactions:

- **List View**: See all transactions with filtering and search
- **Detail View**: View complete transaction details including metadata
- **Filters**: Filter by status, type, date, subscriptions
- **Export**: Export transaction data (via Filament actions)

Features:
- Transaction ID with copy functionality
- Status badges (Pending, Completed, Failed, etc.)
- Amount display with currency
- Subscription indicator
- Refund tracking

Access via: **Admin Panel → Payment Gateway → Transactions**

### 3. Payment Token Resource

Manage saved payment methods:

- **List View**: View all saved payment tokens
- **Edit**: Manage token settings (default, active status)
- **View**: See complete token details
- **Delete**: Remove payment tokens

Features:
- Card brand badges
- Masked card numbers (ending digits)
- Expiry date display
- Default payment method indicator
- Last used tracking

Access via: **Admin Panel → Payment Gateway → Payment Tokens**

## Using Laravel Spatie Settings

The payment settings are managed using [Laravel Spatie Settings](https://github.com/spatie/laravel-settings).

### Accessing Settings in Code

```php
use Sumit\LaravelPayment\Settings\PaymentSettings;

// Get settings
$settings = app(PaymentSettings::class);

// Access properties
$companyId = $settings->company_id;
$apiKey = $settings->api_key;

// Update settings
$settings->company_id = 'new-company-id';
$settings->save();
```

### Migrating from Config to Settings

If you're migrating from the config-based approach, the initial settings migration will automatically populate settings from your config values.

## Customization

### Custom Resources

You can extend the default resources to add custom functionality:

```php
namespace App\Filament\Resources;

use Sumit\LaravelPayment\Filament\Resources\TransactionResource as BaseTransactionResource;

class TransactionResource extends BaseTransactionResource
{
    // Add your customizations here
    
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                // Add custom columns
            ]);
    }
}
```

Then register your custom resource instead of the default one in your Panel Provider.

### Custom Actions

Add custom actions to resources:

```php
use Filament\Tables;
use Sumit\LaravelPayment\Services\RefundService;

// In your custom TransactionResource
public static function table(Table $table): Table
{
    return parent::table($table)
        ->actions([
            Tables\Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (Transaction $record, RefundService $refundService) {
                    $result = $refundService->processRefund($record);
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Refund processed successfully')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Refund failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (Transaction $record) => $record->status === 'completed'),
        ]);
}
```

## Webhooks Dashboard

To view webhook activity, you can create a custom page or use the transaction metadata to track webhook events.

### Example Custom Webhook Log Page

```php
namespace App\Filament\Pages;

use Filament\Pages\Page;

class WebhookLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string $view = 'filament.pages.webhook-logs';
    
    protected static ?string $navigationGroup = 'Payment Gateway';

    public function getWebhookLogs()
    {
        // Fetch transactions with webhook data
        return Transaction::whereNotNull('metadata->webhook_data')
            ->latest()
            ->get();
    }
}
```

## Permissions

You can use Filament's built-in authorization to control access to payment resources:

```php
use Filament\Facades\Filament;

// In your AuthServiceProvider or Policy
Gate::define('view-transactions', function ($user) {
    return $user->hasRole('admin');
});

// In your TransactionResource
public static function canViewAny(): bool
{
    return Gate::allows('view-transactions');
}
```

## Scheduled Tasks

For recurring billing, add this to your `app/Console/Kernel.php`:

```php
use Sumit\LaravelPayment\Services\RecurringBillingService;

protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(RecurringBillingService::class)->processDueSubscriptions();
    })->daily()->at('02:00');
}
```

## Support

For Filament-specific issues:
- Check Filament documentation: https://filamentphp.com/docs
- SUMIT Payment Gateway support: support@sumit.co.il

## Advanced Features

### Charts and Statistics

Add payment statistics to your dashboard:

```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Sumit\LaravelPayment\Models\Transaction;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', '₪' . Transaction::completed()->sum('amount'))
                ->description('All time revenue')
                ->color('success'),
            Stat::make('Active Subscriptions', Transaction::subscriptions()->where('status', 'active')->count())
                ->description('Active recurring subscriptions')
                ->color('primary'),
            Stat::make('Failed Payments', Transaction::failed()->whereDate('created_at', today())->count())
                ->description('Failed today')
                ->color('danger'),
        ];
    }
}
```

## Best Practices

1. **Use Settings for Configuration**: Prefer the Filament settings page over manual config file editing
2. **Monitor Webhooks**: Regularly check webhook logs for failed deliveries
3. **Test in Sandbox**: Always test in testing mode before going live
4. **Backup Settings**: Export settings before major changes
5. **Role-Based Access**: Implement proper role-based access control for sensitive payment operations

## Troubleshooting

### Settings Not Loading

Make sure the settings migration has been run:

```bash
php artisan migrate
```

### Resources Not Appearing

Check that the plugin is registered in your Panel Provider and that you've cleared caches:

```bash
php artisan filament:clear-cached-components
php artisan optimize:clear
```

### Permission Denied

Verify user permissions and that the `canViewAny()` method returns true.
