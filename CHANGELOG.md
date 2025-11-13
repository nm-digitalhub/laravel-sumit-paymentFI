# Changelog

All notable changes to the SUMIT Laravel Payment Gateway package will be documented in this file.

## [2.0.1] - 2024-11-13

### Fixed
- Fixed PaymentStatusChanged event dispatching incorrect old status in WebhookController (captured before update)
- Fixed Carbon date parsing in RecurringBillingService using proper Carbon::parse() method
- Improved database compatibility by replacing MySQL-specific JSON_EXTRACT with Laravel's JSON query syntax
- Fixed webhook route middleware configuration (changed from array to string parameter)
- Updated Filament dependency to v4.1 for latest compatibility

### Changed
- Upgraded Filament from v3.0 to v4.1
- Updated TransactionResource to use TextColumn with badge() instead of deprecated BadgeColumn
- Updated all documentation to reference Filament v4.1

## [2.0.0] - 2024-11-13

### Added
- **Filament Admin Panel Integration**
  - Complete Filament plugin for admin panel integration
  - Transaction Resource with list, view, and filtering capabilities
  - PaymentToken Resource for managing saved payment methods
  - Settings Page for managing payment gateway configuration through UI
  - Real-time status updates and comprehensive filtering options
  - Export capabilities for transaction data

- **Laravel Spatie Settings Integration**
  - PaymentSettings class for modern settings management
  - Database-backed settings instead of config files
  - Settings migration for easy initialization
  - Dynamic settings updates through Filament UI

- **Refund System**
  - RefundService for processing full and partial refunds
  - Refund tracking on transactions (amount, status)
  - RefundProcessed event for refund lifecycle hooks
  - API integration for creating credit invoices
  - Automatic refund status management (full/partial)

- **Recurring Billing & Subscriptions**
  - RecurringBillingService for subscription management
  - Create, update, and cancel subscriptions
  - Automated subscription charging with scheduled tasks
  - Support for multiple billing frequencies (daily, weekly, monthly, yearly)
  - Failed payment retry logic with automatic cancellation
  - Subscription events (SubscriptionCreated, SubscriptionCharged)

- **Webhooks**
  - WebhookController for handling payment status updates
  - Webhook signature validation
  - Support for multiple event types:
    - payment.completed
    - payment.failed
    - payment.refunded
    - payment.authorized
    - subscription.charged
    - subscription.failed
  - WebhookReceived and PaymentStatusChanged events
  - Comprehensive webhook logging

- **New Events**
  - RefundProcessed - Triggered when a refund is processed
  - SubscriptionCreated - Triggered when a subscription is created
  - SubscriptionCharged - Triggered when a subscription is charged
  - WebhookReceived - Triggered for all incoming webhooks
  - PaymentStatusChanged - Triggered when transaction status changes via webhook

- **Database Enhancements**
  - Migration for refund fields (refund_amount, refund_status)
  - Transaction type field (payment, subscription, refund)
  - Payment token relationship on transactions
  - Additional indexes for better query performance

- **Documentation**
  - FILAMENT_INTEGRATION.md - Complete Filament integration guide
  - Updated README with new features and examples
  - Examples for refunds, subscriptions, and webhooks
  - Installation and configuration instructions for Filament

- **Testing**
  - Unit tests for RefundService
  - Unit tests for RecurringBillingService
  - Unit tests for PaymentSettings
  - Feature tests for WebhookController
  - Test coverage for validation and business logic

### Changed
- Enhanced Transaction model with refund fields and payment token relationship
- Updated ServiceProvider to register new services (RefundService, RecurringBillingService)
- Improved composer.json with Filament and Spatie Settings dependencies
- Extended routes to include webhook endpoint
- Updated README with comprehensive feature documentation

### Enhanced
- Transaction Resource with refund tracking
- Token management with relationship to transactions
- Settings management with Filament UI instead of manual config editing
- Event system with more granular payment lifecycle hooks

### Technical Improvements
- Better separation of concerns with dedicated services
- More comprehensive test coverage
- Improved documentation and examples
- Enhanced error handling and validation
- Better database indexing for performance

## [1.0.0] - 2024-11-13

### Added
- Initial release of Laravel package converted from WooCommerce plugin
- Service Provider for package registration and bootstrapping
- Database migrations for payment tokens, transactions, and customers
- Eloquent models for PaymentToken, Transaction, and Customer
- ApiService for SUMIT API communication
- PaymentService for payment processing logic
- TokenService for secure token management
- PaymentController for HTTP payment endpoints
- TokenController for token management endpoints
- Event system with PaymentCompleted, PaymentFailed, and TokenCreated events
- Example event listeners for logging
- Comprehensive configuration file with all settings
- Routes for payment processing and token management
- Facade for easy service access
- Middleware for request validation
- Support for direct and redirect payment flows
- Support for tokenized payments (J2/J5)
- Support for installment payments
- Support for subscription payments
- Support for donation receipts
- Multi-currency support
- VAT calculation support
- Comprehensive documentation in README

### Changed from WooCommerce Plugin
- Converted WordPress hooks to Laravel events
- Replaced WooCommerce order system with generic transaction system
- Migrated from WordPress database to Laravel migrations
- Converted global functions to service classes
- Replaced WordPress HTTP API with Guzzle
- Converted plugin settings to Laravel configuration
- Migrated from procedural to object-oriented architecture
- Updated authentication from WordPress users to Laravel auth

### Features Preserved
- All payment processing functionality
- Credit card tokenization
- Invoice and receipt generation
- Recurring billing support
- Stock synchronization capabilities (to be implemented by users)
- Multiple marketplace support (to be implemented by users)
- Secure API communication
- Logging and debugging
- Error handling and validation

### Security
- PCI DSS compliant tokenization
- Sensitive data sanitization in logs
- HTTPS-only API communication
- User authentication for token management
- Transaction ownership validation
