# Package Architecture Summary

## Overview

This package has been refactored to follow a **zero-assumptions, event-driven architecture**, making it 100% modular and customizable for any Laravel project.

---

## Core Principles

### 1. Zero Assumptions ✨

The package does NOT assume:
- ❌ Your model names (User, Order, Invoice, etc.)
- ❌ Your database schema
- ❌ Your business logic
- ❌ Your frontend framework
- ❌ Your admin panel

### 2. Event-Driven Architecture 🎯

All operations fire events with:
- ✅ DTOs (Data Transfer Objects) for type safety
- ✅ Primitive data (strings, floats, arrays)
- ✅ NO Eloquent models

### 3. Optional Everything 🔧

Everything is optional:
- ✅ Models can be disabled
- ✅ Migrations load conditionally
- ✅ Listeners register conditionally
- ✅ User decides what to use

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                      YOUR APPLICATION                       │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Your Models  │  │Your Listeners│  │Your Business │    │
│  │              │  │              │  │    Logic     │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│         ▲                ▲                   ▲             │
└─────────┼────────────────┼───────────────────┼─────────────┘
          │                │                   │
          │          ┌─────▼──────┐           │
          │          │   Events   │◄──────────┘
          │          │  (DTOs +   │
          │          │ Primitives)│
          │          └─────▲──────┘
          │                │
┌─────────┼────────────────┼───────────────────────────────────┐
│         │      SUMIT PAYMENT PACKAGE                        │
│         │                │                                   │
│  ┌──────┴──────┐   ┌────┴──────┐    ┌──────────────┐      │
│  │  Optional   │   │  Services │    │   DTOs       │      │
│  │   Models    │   │  (Generic)│    │ & Contracts  │      │
│  │             │   │           │    │              │      │
│  └─────────────┘   └─────┬─────┘    └──────────────┘      │
│                          │                                  │
│                    ┌─────▼──────┐                          │
│                    │ API Service│                          │
│                    │  (SUMIT)   │                          │
│                    └─────┬──────┘                          │
└──────────────────────────┼────────────────────────────────┘
                           │
                    ┌──────▼───────┐
                    │ SUMIT Gateway│
                    └──────────────┘
```

---

## Key Components

### DTOs (Data Transfer Objects)

**Purpose:** Type-safe data containers

- `PaymentData` - Payment request data
- `PaymentResponse` - Payment response data
- `TokenData` - Token data
- `RefundData` - Refund request data

**Example:**
```php
$data = new PaymentData(
    amount: 100.00,
    currency: 'ILS',
    customerName: 'John Doe',
    metadata: ['order_id' => 123]
);
```

### Contracts/Interfaces

**Purpose:** Allow custom implementations

- `PaymentGatewayInterface` - Custom payment logic
- `TokenStorageInterface` - Custom token storage
- `WebhookHandlerInterface` - Custom webhook handling

**Example:**
```php
class MyPaymentGateway implements PaymentGatewayInterface {
    public function createPayment(PaymentData $data): PaymentResponse {
        // Custom logic
    }
}
```

### Events

**Purpose:** Bridge between package and app

All events use DTOs and primitive data:

- `PaymentCreated(PaymentResponse, PaymentData)`
- `PaymentCompleted(string $id, float $amount, ...)`
- `PaymentFailed(string $id, string $error, ...)`
- `PaymentRefunded(string $id, float $amount, ...)`
- `TokenCreated(TokenData, mixed $userId)`

### Services

**GenericPaymentService** (New)
- Implements `PaymentGatewayInterface`
- Fires events only
- Does NOT write to database
- 100% model-agnostic

**PaymentService** (Old - Backward Compatible)
- Original implementation
- Writes to Transaction model
- Still available for existing apps

### Optional Models

Package provides optional models:
- `Transaction` - Payment transactions
- `PaymentToken` - Saved payment methods
- `Customer` - Customer data

Can be disabled via config:
```env
SUMIT_TRANSACTION_MODEL=null
```

### Optional Listeners

Package provides optional listeners that auto-register when models are enabled:
- `StorePaymentInDatabase`
- `UpdatePaymentStatus`
- `MarkPaymentAsFailed`
- `StoreTokenInDatabase`
- `RecordRefund`

---

## Data Flow

### Payment Processing Flow

```
User Request
    │
    ▼
Controller (GenericPaymentService)
    │
    ├─► Execute before_payment callback (optional)
    │
    ├─► Build PaymentData DTO
    │
    ├─► Call SUMIT API
    │
    ├─► Build PaymentResponse DTO
    │
    ├─► Fire PaymentCreated event
    │   └─► Optional: StorePaymentInDatabase listener
    │
    ├─► Fire PaymentCompleted/PaymentFailed event
    │   ├─► Optional: UpdatePaymentStatus listener
    │   └─► Your custom listeners
    │
    ├─► Execute after_payment_success callback (optional)
    │
    └─► Return PaymentResponse to controller
```

### Webhook Flow

```
SUMIT Webhook
    │
    ▼
GenericWebhookController
    │
    ├─► Verify signature
    │
    ├─► Parse payload
    │
    ├─► Fire WebhookReceived event
    │
    ├─► Fire specific event (PaymentCompleted, etc.)
    │   ├─► Optional: Package model listeners
    │   └─► Your custom listeners
    │
    └─► Return success response
```

---

## Configuration

### Models Configuration

```php
'models' => [
    'transaction' => null,  // or \Sumit\...\Transaction::class
    'token' => null,
    'customer' => null,
],
```

### Service Bindings

```php
'services' => [
    'payment_gateway' => \Sumit\...\PaymentService::class,
    'token_storage' => null,
],
```

### Callbacks

```php
'callbacks' => [
    'before_payment' => function(PaymentData $data) {
        // Validate, log, etc.
    },
    'after_payment_success' => function(PaymentResponse $response) {
        // Analytics, notifications, etc.
    },
],
```

---

## Usage Patterns

### Pattern 1: Generic Mode (Recommended)

```php
// 1. Disable models
SUMIT_TRANSACTION_MODEL=null

// 2. Listen to events
Event::listen(PaymentCompleted::class, function($event) {
    YourModel::create([
        'transaction_id' => $event->transactionId,
        'amount' => $event->amount,
    ]);
});

// 3. Process payment
$service = app(GenericPaymentService::class);
$response = $service->createPayment(new PaymentData(...));
```

### Pattern 2: Traditional Mode

```php
// 1. Keep models enabled (default)
SUMIT_TRANSACTION_MODEL=Sumit\...\Transaction::class

// 2. Process payment
$service = app(PaymentService::class);
$result = $service->processPayment([...]);

// 3. Query transactions
$transactions = Transaction::where('user_id', auth()->id())->get();
```

### Pattern 3: Hybrid Mode

```php
// 1. Keep models enabled
// 2. Also create your own models

Event::listen(PaymentCompleted::class, function($event) {
    // Package creates Transaction automatically
    
    // You also create your own record
    Order::find($event->metadata['order_id'])->update([
        'payment_status' => 'paid',
    ]);
});
```

---

## Comparison Matrix

| Feature | Old (Tightly-Coupled) | New (Generic) |
|---------|----------------------|---------------|
| Models | Required | Optional |
| Events | Pass Eloquent models | DTOs + primitives |
| Database | Package controls | You control |
| Customization | Limited | Full |
| Your models | Can't use | Can use |
| Backward compat | N/A | Maintained |

---

## File Organization

```
├── src/
│   ├── DTO/                    # NEW: Data Transfer Objects
│   ├── Contracts/              # NEW: Interfaces
│   ├── Events/                 # UPDATED: Model-agnostic
│   ├── Services/
│   │   ├── GenericPaymentService.php    # NEW
│   │   └── PaymentService.php           # OLD
│   ├── Controllers/
│   │   ├── GenericWebhookController.php # NEW
│   │   └── WebhookController.php        # OLD
│   ├── Listeners/ModelListeners/        # NEW: Optional
│   └── Models/                           # OPTIONAL
│
├── config/sumit-payment.php    # UPDATED
│
└── Documentation
    ├── GENERIC_ARCHITECTURE.md
    ├── INTEGRATION_EXAMPLES.md
    ├── QUICK_START.md
    ├── MIGRATION_GUIDE_V2.md
    └── README.md
```

---

## Benefits

### For Package Users

✅ Use your own models  
✅ Control your database schema  
✅ Implement custom business logic  
✅ Package updates won't break your code  
✅ Multiple payment gateways with same listeners  

### For Package Maintainers

✅ Less breaking changes  
✅ Easier to maintain  
✅ More flexible for users  
✅ Clear separation of concerns  
✅ Better testability  

---

## Testing Strategy

### Unit Tests
- DTOs validation
- Service methods
- Event payloads

### Integration Tests
- Payment flow end-to-end
- Webhook handling
- Event listeners

### Feature Tests
- With models enabled
- With models disabled
- Hybrid mode

---

## Version Compatibility

| Version | PHP | Laravel | Architecture |
|---------|-----|---------|--------------|
| 1.x | 8.1+ | 11.x | Tightly-coupled |
| 2.x | 8.1+ | 11.x+ | Generic (this version) |

---

## Migration Path

**From 1.x to 2.x:**

1. Update package
2. Review documentation
3. Choose migration strategy
4. Test in development
5. Deploy to production

**Three migration strategies:**
1. Keep using package models (no changes)
2. Migrate to generic mode (recommended)
3. Hybrid approach

See [MIGRATION_GUIDE_V2.md](MIGRATION_GUIDE_V2.md) for details.

---

## Security Considerations

✅ Package doesn't store card data  
✅ Webhook signature verification  
✅ PCI-compliant tokenization  
✅ Encrypted sensitive data  
✅ Sanitized logs  

---

## Performance

- **Events:** Synchronous by default (can queue)
- **Database:** Only if models enabled
- **API Calls:** Cached when appropriate
- **Webhooks:** Async processing supported

---

## Future Enhancements

Possible future additions:
- More payment gateways
- Queue integration for events
- More DTOs for complex scenarios
- GraphQL API support
- More interfaces for customization

---

## Support & Resources

- 📖 [README.md](README.md) - Installation & basic usage
- 📖 [GENERIC_ARCHITECTURE.md](GENERIC_ARCHITECTURE.md) - Architecture details
- 📖 [INTEGRATION_EXAMPLES.md](INTEGRATION_EXAMPLES.md) - Real-world examples
- 📖 [QUICK_START.md](QUICK_START.md) - Quick start guide
- 📖 [MIGRATION_GUIDE_V2.md](MIGRATION_GUIDE_V2.md) - Migration guide
- 🐛 GitHub Issues - Report bugs
- 💬 GitHub Discussions - Ask questions

---

## Contributing

Contributions welcome! Please:
1. Follow architecture principles
2. Maintain zero-assumptions approach
3. Add tests for new features
4. Update documentation
5. Maintain backward compatibility

---

## License

MIT License - See LICENSE file

---

**Remember:** This package is a library, not a framework. It provides tools and fires events. You decide how to use them.
