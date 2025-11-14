# Laravel Telebirr Payment Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/matirezzo/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/matirezzo/laravel-telebirr)
[![Total Downloads](https://img.shields.io/packagist/dt/matirezzo/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/matirezzo/laravel-telebirr)
[![License](https://img.shields.io/packagist/l/matirezzo/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/matirezzo/laravel-telebirr)

Telebirr payment gateway integration for Laravel applications with support for single and multi-merchant setups.

## Features

- 🚀 **Simple Setup** - Get started in minutes with zero configuration
- 🏪 **Multi-Merchant Support** - Perfect for multi-branch or multi-store applications
- 🔐 **Enterprise Security** - RSA PSS signatures, webhook verification, SSL controls
- ⚡ **High Performance** - Token caching, queue-based processing, retry logic
- 🛠 **Developer Friendly** - Artisan commands, comprehensive logging, extensive configuration
- 🔧 **Highly Configurable** - Extensive customization options for any use case
- 📱 **Mobile Ready** - Raw request generation for Telebirr mobile SDK
- 🎯 **Event Driven** - Laravel events for payment lifecycle hooks

## Installation

### Basic Setup (Single Merchant)

```bash
composer require matirezzo/laravel-telebirr
php artisan telebirr:install
```

### Multi-Merchant Setup

```bash
composer require matirezzo/laravel-telebirr
php artisan telebirr:install --mode=multi --run-migrations
```

## Quick Start

### 1. Configure Environment

Add your Telebirr credentials to `.env`:

```env
# Single merchant mode
TELEBIRR_MODE=single
TELEBIRR_FABRIC_APP_ID=your_fabric_app_id
TELEBIRR_MERCHANT_APP_ID=your_merchant_app_id
TELEBIRR_MERCHANT_CODE=123456
TELEBIRR_APP_SECRET=your_app_secret
TELEBIRR_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

### 2. Create Payment Order

```php
use Telebirr\LaravelTelebirr\Facades\Telebirr;

$rawRequest = Telebirr::initiatePayment([
    'txn_ref' => 'TXN_' . time(),
    'amount' => 150.00,
    'subject' => 'Order Payment'
]);
```

### 3. Handle Webhook (Automatically Done)

The package handles webhook verification, signature checking, and payment verification automatically.

## Usage Examples

### Single Merchant

```php
// Simple payment initiation
Telebirr::initiatePayment([
    'txn_ref' => 'TXN_123',
    'amount' => 100.00,
    'subject' => 'Product Purchase'
]);
```

### Multi-Merchant (Branch-Based)

```php
// Payment for specific branch
Telebirr::initiatePayment([
    'txn_ref' => 'TXN_123',
    'amount' => 100.00,
    'subject' => 'Branch Order'
], ['branch_id' => 1]);
```

### Custom Context

```php
// Organized by stores
Telebirr::initiatePayment($data, ['store_id' => 5]);

// Or even custom types
Telebirr::initiatePayment($data, ['owner_type' => 'restaurant', 'owner_id' => 10]);
```

### Payment Verification

```php
$result = Telebirr::verifyPayment('TXN_123');
if ($result && $result['order_status'] === 'PAY_SUCCESS') {
    // Payment successful
}
```

### Auth Token Retrieval

```php
$userInfo = Telebirr::getAuthToken($accessToken);
```

## Event Listeners

Listen to payment lifecycle events:

```php
Event::listen(Telebirr\LaravelTelebirr\Events\PaymentInitiated::class, function ($event) {
    // Payment initiated
});

Event::listen(Telebirr\LaravelTelebirr\Events\PaymentVerified::class, function ($event) {
    // Payment verified and successful
    // Update your order/invoice status here
});

Event::listen(Telebirr\LaravelTelebirr\Events\WebhookReceived::class, function ($event) {
    // Raw webhook received
});
```

## Testing

Test your integration:

```bash
# Test API connectivity
php artisan telebirr:test-connection

# Setup and test webhooks
php artisan telebirr:setup-webhook --test
```

## Configuration

### Single Merchant Mode
- Uses global ENV configuration
- No database required
- Perfect for simple applications

### Multi-Merchant Mode
- Database-driven merchant management
- Configurable relationship mappings
- Enterprise-ready for complex applications

## API Reference

### Facade Methods

```php
Telebirr::initiatePayment(array $data, array $context = [])
Telebirr::verifyPayment(string $transactionId, array $context = [])
Telebirr::queryOrder(string $orderId, array $context = [])
Telebirr::getAuthToken(string $accessToken, array $context = [])
Telebirr::handleWebhook(Request $request)
```

### HTTP Endpoints

- `POST /api/telebirr/order` - Create payment order
- `POST /api/telebirr/verify` - Verify payment status
- `POST /api/telebirr/query` - Query order details
- `POST /api/telebirr/auth` - Get auth token
- `POST /api/telebirr/webhook` - Webhook handler

## Advanced Configuration

Publish config for full customization:

```bash
php artisan vendor:publish --tag=telebirr-config
```

### Custom Merchant Resolver

```php
// config/telebirr.php
'merchant' => [
    'resolver' => App\Services\CustomMerchantResolver::class,
],
```

### Custom Webhook Handler

```php
'webhook' => [
    'handler' => App\Services\CustomWebhookHandler::class,
],
```

## Migration Guide

### From Single to Multi-Merchant

1. Change mode: `TELEBIRR_MODE=multi`
2. Run: `php artisan telebirr:install --mode=multi --run-migrations`
3. Add merchant records to `telebirr_merchants` table
4. Update code to pass merchant context where needed

## Security

- RSA PSS signature validation
- Webhook signature verification with timestamp tolerance
- SSL certificate validation (configurable)
- Comprehensive audit logging
- Configurable sensitive data masking

## Performance

- Fabric token caching (55-minute TTL)
- Queue-based payment verification
- Retry logic with exponential backoff
- Database connection pooling aware
- Optimized queries with proper indexing

## Requirements

- PHP 8.1+
- Laravel 9.0+
- phpseclib 3.0+

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- 📖 Documentation: https://laravel-telebirr.com
- 🐛 Issues: [GitHub Issues](https://github.com/matirezzo/laravel-telebirr/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/matirezzo/laravel-telebirr/discussions)
