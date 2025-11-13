# Changelog

All notable changes to the Laravel OfficeGuy package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release of Laravel OfficeGuy package
- Complete conversion from WooCommerce plugin to Laravel package
- Payment processing with SUMIT API integration
- Token management for secure card storage
- Subscription payment support
- Stock synchronization from SUMIT
- Database migrations for payments, tokens, customers, and stock logs
- Eloquent models with relationships
- Service classes for clean architecture
- Laravel events system for payment lifecycle
- API routes for payment processing
- Webhook handling for payment callbacks
- Console commands for stock sync and credential testing
- Comprehensive documentation
- Migration guide from WooCommerce
- Helper classes for payment utilities
- Default event listeners for logging

### Features
- **Payment Processing**: Secure credit card processing via SUMIT API
- **Token Storage**: PCI-compliant token storage for recurring payments
- **Invoice Generation**: Automatic invoice/receipt creation
- **Subscription Support**: Recurring payment handling
- **Stock Sync**: Inventory synchronization with SUMIT
- **Multi-currency**: Support for ILS, USD, EUR, GBP
- **Event System**: Laravel events for extensibility
- **Webhook Support**: Handle payment callbacks
- **Logging**: Comprehensive logging system
- **Middleware**: Authentication and webhook verification

### Documentation
- README.md with installation and usage instructions
- INSTALLATION.md with step-by-step setup guide
- MIGRATION.md for migrating from WooCommerce plugin
- Inline code documentation
- API examples and usage patterns

### Technical Details
- Compatible with Laravel 11.x and 12.x
- Requires PHP 8.1 or higher
- Uses Guzzle for HTTP requests
- Eloquent ORM for database operations
- PSR-4 autoloading
- Service container integration
- Facade support for easy access

## [Unreleased]

### Planned
- Unit tests for all services
- Integration tests for payment flow
- Feature tests for controllers
- Additional payment methods support
- Enhanced error handling
- Rate limiting for API calls
- Caching for frequently accessed data
- Queue support for stock synchronization
- Notification system for payment events
- Admin dashboard views
- API documentation generator
- Postman collection for API testing
