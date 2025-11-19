# CRM Synchronization Guide

This guide explains how to use the bidirectional CRM synchronization feature for managing customers between your local database and SUMIT CRM.

## Overview

The CRM synchronization feature allows you to:
- **Pull customers** from SUMIT CRM to your local database
- **Push customers** from your local database to SUMIT CRM
- **Bidirectional sync** - automatically pull and push in one operation

## Features

### Header Actions (List Customers Page)

#### Pull from CRM
- **Location**: Customer list page header
- **Icon**: Arrow down tray
- **Action**: Fetches all customers from SUMIT CRM and creates/updates them in your local database
- **Confirmation**: Required before execution
- **Notification**: Shows success/failure status and count of synced customers

#### Bidirectional Sync
- **Location**: Customer list page header
- **Icon**: Arrow path (circular)
- **Action**: Performs both pull (from CRM) and push (to CRM) operations
- **Confirmation**: Required before execution
- **Notification**: Shows detailed results including counts for both operations

### Row Actions (Individual Customer)

#### Push to CRM
- **Location**: Actions column in customer table
- **Icon**: Arrow up tray
- **Action**: Pushes the selected customer to SUMIT CRM
- **Confirmation**: Required, shows customer name
- **Notification**: Shows success/failure status

### Bulk Actions (Multiple Customers)

#### Push to CRM
- **Location**: Bulk actions menu (when customers are selected)
- **Icon**: Arrow up tray
- **Action**: Pushes all selected customers to SUMIT CRM
- **Confirmation**: Required
- **Notification**: Shows count of successful and failed pushes

## Usage

### Syncing All Customers from CRM

1. Navigate to **Admin Panel → Payment Gateway → Customers**
2. Click the **"Pull from CRM"** button in the page header
3. Confirm the action in the modal dialog
4. Wait for the synchronization to complete
5. A notification will show the number of customers synced

### Pushing a Single Customer to CRM

1. Navigate to the customer list
2. Find the customer you want to push
3. Click the **"Push to CRM"** action in the row
4. Confirm the action
5. A notification will confirm success or show an error

### Pushing Multiple Customers to CRM

1. Navigate to the customer list
2. Select the customers you want to push (checkboxes)
3. Click the **"Bulk Actions"** dropdown
4. Select **"Push to CRM"**
5. Confirm the action
6. A notification will show how many were successfully pushed

### Bidirectional Synchronization

1. Navigate to **Admin Panel → Payment Gateway → Customers**
2. Click the **"Bidirectional Sync"** button in the page header
3. Confirm the action in the modal dialog
4. The system will:
   - First pull all customers from SUMIT CRM
   - Then push all local customers to SUMIT CRM
5. A notification will show detailed results for both operations

## API Integration

The CRM synchronization uses the following SUMIT API endpoints:

### Pull from CRM
- **Endpoint**: `/website/customers/getlist/`
- **Method**: POST
- **Credentials**: CompanyID, APIKey
- **Response**: List of customers with their details

### Push to CRM
- **Endpoint**: `/website/customers/update/`
- **Method**: POST
- **Credentials**: CompanyID, APIKey
- **Data**: Customer details including CustomerID, Name, Email, etc.

## Data Mapping

The following fields are synchronized between local database and SUMIT CRM:

| Local Field | SUMIT Field | Type |
|------------|-------------|------|
| sumit_customer_id | CustomerID | string |
| name | Name | string |
| email | Email | string |
| phone | Phone | string |
| company_name | CompanyName | string |
| tax_id | TaxID | string |
| address | Address | string |
| city | City | string |
| state | State | string |
| country | Country | string (default: IL) |
| zip_code | ZipCode | string |
| metadata | (custom fields) | JSON |

## Metadata Tracking

When a customer is synced from CRM, the system automatically adds a `last_synced_at` timestamp to the customer's metadata:

```php
'metadata' => [
    'last_synced_at' => '2024-01-01T12:00:00+00:00',
    // ... other custom fields
]
```

## Error Handling

The synchronization service includes comprehensive error handling:

- **API Connection Errors**: Logged and reported to user
- **Invalid Credentials**: Shows clear error message
- **Missing Customer Data**: Skips invalid records
- **Partial Failures**: Reports counts of successful and failed operations

### Example Error Notifications

#### Pull Failure
```
Title: Sync failed
Body: No response from SUMIT API
Type: Danger
```

#### Partial Push Failure
```
Title: Push completed with issues
Body: Pushed 8 customers, 2 failed
Type: Warning
```

## Programmatic Usage

You can also use the CRM sync service programmatically:

### Pull Customers

```php
use Sumit\LaravelPayment\Services\CrmSyncService;

$crmSync = app(CrmSyncService::class);
$result = $crmSync->pullCustomersFromCrm();

if ($result['success']) {
    echo "Synced {$result['synced']} customers";
} else {
    echo "Error: {$result['message']}";
}
```

### Push Single Customer

```php
use Sumit\LaravelPayment\Models\Customer;
use Sumit\LaravelPayment\Services\CrmSyncService;

$customer = Customer::find(1);
$crmSync = app(CrmSyncService::class);
$result = $crmSync->pushCustomerToCrm($customer);

if ($result['success']) {
    echo "Customer pushed successfully";
}
```

### Bidirectional Sync

```php
use Sumit\LaravelPayment\Services\CrmSyncService;

$crmSync = app(CrmSyncService::class);

// Sync all customers
$result = $crmSync->bidirectionalSync();

// Or sync specific customers
$result = $crmSync->bidirectionalSync([1, 2, 3]);

echo $result['message'];
echo "Pulled: {$result['details']['pull']['synced']}";
echo "Pushed: {$result['details']['push']['synced']}";
```

## Scheduled Synchronization

You can set up automatic synchronization using Laravel's scheduler. Add to your `app/Console/Kernel.php`:

```php
use Sumit\LaravelPayment\Services\CrmSyncService;

protected function schedule(Schedule $schedule)
{
    // Sync customers daily at 3 AM
    $schedule->call(function () {
        app(CrmSyncService::class)->bidirectionalSync();
    })->daily()->at('03:00');
}
```

## Best Practices

1. **Regular Syncing**: Schedule regular syncs to keep data up-to-date
2. **Test First**: Use the pull action first to verify API credentials
3. **Backup**: Always backup your database before bulk operations
4. **Monitor Logs**: Check Laravel logs for detailed sync operation info
5. **Handle Conflicts**: Last write wins - newer data overwrites older data

## Troubleshooting

### No Response from API
- Check your internet connection
- Verify SUMIT API is accessible
- Check firewall settings

### Invalid Credentials
- Verify `SUMIT_COMPANY_ID` and `SUMIT_API_KEY` in `.env`
- Ensure credentials have CRM access permissions
- Test credentials using the Payment Settings page

### Partial Sync Failures
- Check Laravel logs for specific errors
- Verify customer data is valid (required fields present)
- Ensure SUMIT customer IDs are unique

### Permission Denied
- Verify user has access to Customer resource
- Check Filament panel permissions
- Ensure proper role-based access control

## Security Considerations

- All API calls use HTTPS (in production environment)
- Credentials are never logged
- Customer data is validated before sync
- Failed operations are logged for audit
- Sensitive data can be excluded via metadata filtering

## Logging

All sync operations are logged to the configured Laravel log channel:

```
[DEBUG] SUMIT API Request
[DEBUG] SUMIT API Response
[ERROR] CRM Pull Error: [error message]
[ERROR] CRM Push Error: [error message]
```

Configure logging in `config/sumit-payment.php`:

```php
'logging' => [
    'enabled' => true,
    'channel' => env('SUMIT_LOG_CHANNEL', 'stack'),
],
```
