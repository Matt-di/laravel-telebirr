# Changelog

All notable changes to `laravel-telebirr` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of Laravel Telebirr Package 📦
- Single merchant mode for simple applications ✅
- Multi-merchant mode for complex enterprise setups ✅
- RSA PSS signature encryption and validation 🔐
- Queue-based payment verification system ⚡
- Comprehensive webhook handling and validation 🪝
- Laravel events for payment lifecycle hooks 🎯
- Configurable merchant context resolution 🏪
- Token caching with configurable TTL ⏰
- Complete artisan command suite for installation/testing 🔧
- Professional documentation and examples 📚
- Enterprise-grade test suite (79% coverage) 🧪

### Features
- **Payment Initiation**: Generate QR codes and mobile payment requests
- **Payment Verification**: Background job processing for status checks
- **Webhook Processing**: Automatic signature validation and order updates
- **Multi-Merchant Support**: Polymorphic merchant relationships
- **Security**: RSA PSS encryption, SHA256 signatures, SSL controls
- **Performance**: Token caching, retry logic, optimistic locking
- **Developer Experience**: Auto-discovery, facade, configuration publishing

## [1.0.0] - 2025-11-14

### Added
- 🚀 **Payment Gateway Integration**: Full Telebirr API integration
- 🏪 **Merchant Management**: Single and multi-merchant configurations
- 🔐 **Cryptographic Security**: RSA PSS signature implementation
- ⚡ **Queue Processing**: Background payment verification
- 🪝 **Webhook System**: Secure webhook handling with signature verification
- 🎯 **Event System**: PaymentInitiated, PaymentVerified, WebhookReceived events
- 🔧 **Artisan Tools**: Installation, testing, and configuration commands
- 📚 **Documentation**: Comprehensive usage guides and API reference
- 🧪 **Testing Suite**: 34 test cases covering core functionality

### Technical Details
- **PHP Support**: ^8.1 (8.1, 8.2, 8.3)
- **Laravel Support**: ^9.0, ^10.0, ^11.0
- **Security**: RSA PSS signatures, webhook validation
- **Performance**: Token caching, background processing, retry logic
- **Architecture**: Driver pattern, service layer, dependency injection

### Breaking Changes
- None (first release)

---

## Types of changes
- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` in case of vulnerabilities

## Development Notes

This package was developed to solve common Telebirr integration pain points in Laravel applications:

- **Simplified Setup**: Zero-configuration for basic use cases
- **Enterprise Ready**: Multi-merchant support for complex applications
- **Security First**: Cryptographic signing and validation
- **Performance Optimized**: Background processing and caching
- **Developer Friendly**: Comprehensive documentation and testing

For questions or issues, please visit the [GitHub repository](https://github.com/matirezzo/laravel-telebirr).
