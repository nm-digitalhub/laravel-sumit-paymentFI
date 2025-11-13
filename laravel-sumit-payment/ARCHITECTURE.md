# Architecture Documentation

This document describes the architecture and design patterns used in the SUMIT Payment Gateway for Laravel package.

## Overview

The package follows Laravel best practices and implements a service-oriented architecture with clear separation of concerns.

## Directory Structure

```
src/
├── Controllers/          # HTTP Controllers for payment endpoints
│   ├── PaymentController.php
│   ├── TokenController.php
│   └── WebhookController.php
├── Events/              # Laravel Events for payment lifecycle
│   ├── PaymentCompleted.php
│   ├── PaymentFailed.php
│   ├── PaymentStatusChanged.php
│   ├── RefundProcessed.php
│   ├── SubscriptionCharged.php
│   ├── SubscriptionCreated.php
│   ├── TokenCreated.php
│   └── WebhookReceived.php
├── Facades/             # Laravel Facades
│   └── SumitPayment.php
├── Filament/           # Filament Admin Panel Integration
│   ├── Pages/
│   │   └── ManagePaymentSettings.php
│   ├── Resources/
│   │   ├── PaymentTokenResource.php
│   │   ├── PaymentTokenResource/Pages/
│   │   ├── TransactionResource.php
│   │   └── TransactionResource/Pages/
│   └── SumitPaymentPlugin.php
├── Listeners/          # Event Listeners
│   ├── LogPaymentCompletion.php
│   └── LogPaymentFailure.php
├── Middleware/         # HTTP Middleware
│   └── ValidatePaymentRequest.php
├── Models/             # Eloquent Models
│   ├── Customer.php
│   ├── PaymentToken.php
│   └── Transaction.php
├── Services/           # Business Logic Services
│   ├── ApiService.php
│   ├── PaymentService.php
│   ├── RecurringBillingService.php
│   ├── RefundService.php
│   └── TokenService.php
├── Settings/           # Spatie Settings
│   └── PaymentSettings.php
└── SumitPaymentServiceProvider.php
```

## Core Components

### 1. Service Layer

The service layer contains all business logic and API communication.

#### ApiService
- **Responsibility**: Communication with SUMIT API
- **Key Methods**:
  - `post($data, $endpoint, $includeClientIp)` - Make POST requests to SUMIT
  - `get($endpoint)` - Make GET requests to SUMIT
  - `getUrl($path)` - Build API URLs based on environment

#### PaymentService
- **Responsibility**: Payment processing logic
- **Key Methods**:
  - `processPayment($paymentData)` - Process a payment
  - `processPaymentWithToken($paymentData, $tokenId)` - Process with saved token
  - `tokenizeCard($cardData, $userId)` - Create payment token
- **Dependencies**: ApiService, TokenService

#### TokenService
- **Responsibility**: Payment token management
- **Key Methods**:
  - `createToken($tokenData, $userId)` - Create new token
  - `getToken($tokenId, $userId)` - Retrieve token
  - `getUserTokens($userId)` - Get all user tokens
  - `setDefaultToken($tokenId, $userId)` - Set default payment method
  - `deleteToken($tokenId, $userId)` - Delete token

#### RefundService
- **Responsibility**: Refund processing
- **Key Methods**:
  - `processRefund($transaction, $amount, $reason)` - Process refund
  - `getRefundDetails($transaction)` - Get refund information
  - `canRefund($transaction)` - Check if refund is possible
- **Dependencies**: ApiService

#### RecurringBillingService
- **Responsibility**: Subscription management
- **Key Methods**:
  - `createSubscription($subscriptionData)` - Create subscription
  - `chargeSubscription($subscriptionData)` - Charge subscription
  - `cancelSubscription($subscription)` - Cancel subscription
  - `updateSubscription($subscription, $updates)` - Update subscription
  - `processDueSubscriptions()` - Process all due subscriptions
- **Dependencies**: PaymentService, TokenService

### 2. Model Layer

Eloquent models represent data structures and relationships.

#### Transaction
- **Purpose**: Represents payment transactions
- **Relationships**:
  - `user()` - Belongs to user
  - `paymentToken()` - Belongs to payment token
- **Scopes**:
  - `completed()` - Only completed transactions
  - `pending()` - Only pending transactions
  - `failed()` - Only failed transactions
  - `subscriptions()` - Only subscription transactions

#### PaymentToken
- **Purpose**: Represents saved payment methods
- **Relationships**:
  - `user()` - Belongs to user
- **Scopes**:
  - `active()` - Only active tokens
  - `default()` - Only default tokens

#### Customer
- **Purpose**: Represents SUMIT customer records
- **Relationships**:
  - `user()` - Belongs to user
- **Methods**:
  - `findBySumitId($sumitId)` - Find by SUMIT ID
  - `findOrCreateByUser($userId, $data)` - Get or create customer

### 3. Event System

Events provide hooks into the payment lifecycle.

#### Event Flow

```
Payment Request
    ↓
PaymentService.processPayment()
    ↓
ApiService.post()
    ↓
Transaction Created/Updated
    ↓
Event Dispatched (PaymentCompleted/PaymentFailed)
    ↓
Listeners Execute
```

#### Event Types

1. **Transaction Events**
   - `PaymentCompleted` - Payment successful
   - `PaymentFailed` - Payment failed
   - `PaymentStatusChanged` - Status updated via webhook

2. **Token Events**
   - `TokenCreated` - New payment token created

3. **Refund Events**
   - `RefundProcessed` - Refund completed

4. **Subscription Events**
   - `SubscriptionCreated` - New subscription created
   - `SubscriptionCharged` - Subscription charged

5. **Webhook Events**
   - `WebhookReceived` - Webhook received from SUMIT

### 4. Controller Layer

Controllers handle HTTP requests and return responses.

#### PaymentController
- **Routes**:
  - `POST /sumit/payment/process` - Process payment
  - `GET /sumit/payment/callback` - Handle redirect callback
  - `GET /sumit/payment/{id}` - Get transaction details

#### TokenController
- **Routes**:
  - `GET /sumit/tokens` - List user tokens
  - `POST /sumit/tokens` - Create token
  - `PUT /sumit/tokens/{id}/default` - Set default
  - `DELETE /sumit/tokens/{id}` - Delete token

#### WebhookController
- **Routes**:
  - `POST /sumit/webhook` - Handle webhooks
- **Features**:
  - Signature validation
  - Event type routing
  - Automatic status updates

### 5. Filament Integration

Filament provides admin panel UI for managing payments.

#### SumitPaymentPlugin
- **Purpose**: Registers Filament resources and pages
- **Components**: Settings page, Transaction resource, Token resource

#### ManagePaymentSettings
- **Purpose**: Settings management page
- **Features**: All payment configuration options in UI

#### TransactionResource
- **Purpose**: Transaction management
- **Features**: List, view, filter, export transactions

#### PaymentTokenResource
- **Purpose**: Token management
- **Features**: List, view, edit, delete tokens

## Data Flow

### Payment Processing

```
1. User submits payment form
   ↓
2. Controller validates request
   ↓
3. PaymentService.processPayment()
   ↓
4. Transaction created (status: pending)
   ↓
5. ApiService.post() to SUMIT API
   ↓
6. Response processed
   ↓
7. Transaction updated (status: completed/failed)
   ↓
8. Event dispatched (PaymentCompleted/PaymentFailed)
   ↓
9. Listeners execute (email, logging, etc.)
   ↓
10. Response returned to user
```

### Webhook Processing

```
1. SUMIT sends webhook
   ↓
2. WebhookController.handle()
   ↓
3. Signature validated
   ↓
4. Event type determined
   ↓
5. Specific handler method called
   ↓
6. Transaction status updated
   ↓
7. Events dispatched
   ↓
8. Response sent to SUMIT
```

### Subscription Charging

```
1. Scheduled task runs
   ↓
2. RecurringBillingService.processDueSubscriptions()
   ↓
3. Find subscriptions where next_billing_date <= today
   ↓
4. For each subscription:
   a. Get payment token
   b. Charge via PaymentService
   c. Update next_billing_date
   d. Handle failures (retry logic)
   ↓
5. Return results summary
```

## Design Patterns

### Service Pattern
- Business logic encapsulated in service classes
- Clear separation of concerns
- Testable and maintainable

### Repository Pattern (via Eloquent)
- Data access through Eloquent models
- Abstraction over database operations
- Relationship management

### Event-Driven Architecture
- Decoupled components via events
- Extensible without modifying core
- Async processing support

### Dependency Injection
- Services injected via constructor
- Laravel service container
- Easy mocking for tests

## Extension Points

### 1. Custom Payment Logic

Extend PaymentService:

```php
class CustomPaymentService extends PaymentService
{
    protected function buildPaymentRequest($data, $transaction)
    {
        $request = parent::buildPaymentRequest($data, $transaction);
        // Add custom fields
        return $request;
    }
}
```

### 2. Custom Event Listeners

```php
Event::listen(PaymentCompleted::class, function ($event) {
    // Custom logic
});
```

### 3. Custom Filament Resources

Extend default resources or create new ones.

### 4. Middleware

Add custom middleware to routes:

```php
Route::middleware(['custom.middleware'])->group(function () {
    // Routes
});
```

## Security Considerations

1. **PCI Compliance**: Card data never stored in database
2. **Token Encryption**: Tokens encrypted at rest
3. **HTTPS Only**: All API communication over HTTPS
4. **Webhook Validation**: Signature verification for webhooks
5. **User Authorization**: Token ownership validation
6. **Input Sanitization**: All inputs validated and sanitized

## Performance Optimization

1. **Database Indexing**: Indexes on frequently queried fields
2. **Eager Loading**: Relationships loaded efficiently
3. **Caching**: Config and settings cached
4. **Queue Support**: Event listeners can be queued
5. **Pagination**: Large result sets paginated

## Testing Strategy

1. **Unit Tests**: Service logic and validation
2. **Feature Tests**: HTTP endpoints and flows
3. **Integration Tests**: API communication (mocked)
4. **Browser Tests**: Filament UI (optional)

## Maintenance

### Logging

All payment operations logged:
- API requests/responses
- Webhook events
- Transaction changes
- Errors and exceptions

### Monitoring

Recommended monitoring:
- Failed payment rate
- Webhook delivery success
- API response times
- Subscription charge success rate

## Future Roadmap

1. **GraphQL API** - Alternative API interface
2. **Real-time Notifications** - WebSocket support
3. **Advanced Analytics** - Dashboard widgets
4. **Mobile SDK** - React Native/Flutter support
5. **Multi-tenant Support** - Multiple merchant accounts

## Contributing

See CONTRIBUTING.md for development guidelines.

## License

MIT License - See LICENSE file.
