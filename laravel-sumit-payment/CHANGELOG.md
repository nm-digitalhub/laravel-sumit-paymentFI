# Changelog

All notable changes to the Laravel SUMIT Payment package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release of Laravel SUMIT Payment Gateway package
- Complete migration from WooCommerce SUMIT Payment Gateway plugin to Laravel
- Payment processing functionality with support for:
  - Credit card payments (direct and redirect flow)
  - PCI compliance options (PCI-compliant, non-PCI, redirect)
  - Payment token storage and management
  - Installment payments (up to configurable maximum)
  - Subscription/recurring payments
  - Multi-currency support (35+ currencies)
- Database structure:
  - Payment tokens table for secure card storage
  - Transactions table for payment tracking
  - Customers table for customer data
  - Documents table for invoices and receipts
- Eloquent models:
  - PaymentToken model with relationships
  - Transaction model with scopes and helpers
  - Customer model with address formatting
  - Document model with type checking
- Service layer:
  - SumitApiService for API communication
  - PaymentService for payment processing
  - TokenService for token management
  - InvoiceService for document creation
- Controllers:
  - PaymentController for payment endpoints
  - WebhookController for callback handling
  - TokenController for token management
- Laravel Events system:
  - PaymentProcessing event
  - PaymentCompleted event
  - PaymentFailed event
  - InvoiceCreated event
  - TokenCreated event
- Custom hooks system (WooCommerce compatibility):
  - MaximumInstallments hook
  - CustomerFields hook
  - ItemFields hook
- Middleware:
  - ValidateWebhookSignature for webhook security
  - ValidateCurrency for currency validation
  - ValidatePaymentAmount for amount and installment validation
- Configuration system with extensive options
- RESTful API routes for payment operations
- Comprehensive documentation:
  - README with installation and usage
  - EXAMPLES with code samples
  - HOOKS documentation for custom hooks
  - CHANGELOG for version tracking
- Support for Laravel 11 and 12
- PHP 8.2+ support

### Features
- **Secure Payment Processing**
  - PCI-compliant card handling
  - Tokenization for recurring payments
  - Webhook verification
  - Client IP tracking for fraud prevention

- **Flexible Configuration**
  - Environment-based settings
  - Multiple merchant numbers support
  - Configurable installments
  - VAT handling options
  - Draft document support
  - Email delivery options

- **Developer-Friendly**
  - Laravel-native implementation
  - Event-driven architecture
  - Facade for easy access
  - Comprehensive error handling
  - Extensive logging support

- **Database Integration**
  - Full transaction history
  - Soft deletes for data integrity
  - Relationships between models
  - Query scopes for filtering

### Migration Notes
This package is a complete rewrite of the WooCommerce SUMIT Payment Gateway plugin for Laravel. Key architectural changes:

- **WordPress Hooks → Laravel Events**: All WooCommerce actions/filters converted to Laravel events
- **Custom Post Types → Eloquent Models**: WordPress metadata replaced with proper database tables and models
- **WordPress Options → Laravel Config**: Settings migrated to Laravel configuration system
- **WordPress Admin → RESTful API**: Admin interface replaced with API endpoints
- **Plugin Structure → Package Structure**: Reorganized into Laravel package standards

### Documentation
- Full README with installation guide
- Usage examples for all major features
- Custom hooks documentation
- API endpoint reference
- Migration guide from WooCommerce

### Testing
- Test structure prepared
- Examples for unit and integration tests
- Event testing support

## [Unreleased]

### Planned
- Automated tests suite
- Support for additional payment methods
- Admin panel/dashboard
- Webhook retry mechanism
- Payment analytics and reporting
- Queue support for async operations
- Rate limiting for API calls
- Localization support
- CLI commands for management
- Integration with popular Laravel e-commerce packages

[1.0.0]: https://github.com/nm-digitalhub/laravel-sumit-payment/releases/tag/v1.0.0
