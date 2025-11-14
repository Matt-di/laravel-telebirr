# Laravel Telebirr Payment Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Matt-di/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/Matt-di/laravel-telebirr)
[![Total Downloads](https://img.shields.io/packagist/dt/Matt-di/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/Matt-di/laravel-telebirr)
[![License](https://img.shields.io/packagist/l/Matt-di/laravel-telebirr.svg?style=flat-square)](https://packagist.org/packages/Matt-di/laravel-telebirr)

Telebirr payment gateway integration for Laravel applications with support for single and multi-merchant setups.

## 🎯 Integration Type

**This package is designed for Telebirr Super App integration** where the frontend handles payment requests through the Telebirr mobile application. 

- **Backend Role:** Generates signed payment requests and handles webhooks
- **Frontend Role:** Uses Telebirr mobile SDK to process payments
- **📖 Frontend Integration:** Refer to [Telebirr Developer Documentation](https://developer.telebirr.et/) for mobile app integration details
- **🔗 Official Resources:** Check Telebirr's official documentation for SDK implementation and best practices

## Features

- 🚀 **Simple Setup** - Get started in minutes with zero configuration
- 🏪 **Multi-Merchant Support** - Perfect for multi-branch or multi-store applications
- 🔐 **Enterprise Security** - RSA PSS signatures, webhook verification, SSL controls
- ⚡ **High Performance** - Token caching, queue-based processing, retry logic
- 🛠 **Developer Friendly** - Artisan commands, comprehensive logging, extensive configuration
- 🔧 **Highly Configurable** - Extensive customization options for any use case
- 📱 **Mobile SDK Ready** - Raw request generation for Telebirr mobile applications
- 🎯 **Event Driven** - Laravel events for payment lifecycle hooks

## Installation

### Basic Setup (Single Merchant)

```bash
composer require matt-di/laravel-telebirr
php artisan telebirr:install
```

### Multi-Merchant Setup

```bash
composer require Matt-di/laravel-telebirr
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

### 2. Create Payment Request

```php
use Telebirr\LaravelTelebirr\Facades\Telebirr;

$rawRequest = Telebirr::initiatePayment([
    'txn_ref' => 'TXN_' . time(),
    'amount' => 150.00,
    'subject' => 'Order Payment'
]);

// Returns: "appid=MERCHANT123&merch_code=MC456&nonce_str=abc...&prepay_id=PRE123...&timestamp=202512021200&sign_type=SHA256WithRSA&sign=signed_data..."
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

### Error Handling

```php
try {
    $paymentRequest = Telebirr::initiatePayment([
        'txn_ref' => 'TXN_' . time(),
        'amount' => 150.00,
        'subject' => 'Order Payment',
    ]);

    // Process successful payment initiation

} catch (\Telebirr\LaravelTelebirr\Exceptions\TelebirrException $e) {
    // Handle payment initiation errors
    Log::error('Telebirr payment failed: ' . $e->getMessage());

    // Show user-friendly error message
    return back()->withErrors(['payment' => 'Payment initiation failed. Please try again.']);
}
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

## Complete Integration Example

Here's a complete real-world integration example:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telebirr\LaravelTelebirr\Facades\Telebirr;
use Telebirr\LaravelTelebirr\Exceptions\TelebirrException;

class PaymentController extends Controller
{
    /**
     * Create payment order and initiate Telebirr payment
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'description' => 'required|string|max:255',
        ]);

        // 1. Create order in your system first
        $order = Order::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'currency' => 'ETB',
            'description' => $request->description,
            'status' => 'pending',
            'reference' => 'ORD_' . time() . '_' . auth()->id(),
        ]);

        try {
            // 2. Initiate Telebirr payment
            $paymentRequest = Telebirr::initiatePayment([
                'txn_ref' => $order->reference,
                'amount' => $order->amount,
                'subject' => 'Order #' . $order->id,
                'description' => $order->description,
                'notify_url' => route('webhook.telebirr'),
                'return_url' => route('payment.success', $order->id),
                'timeout_express' => '30m',
            ]);

            // 3. Update order with payment request details
            $order->update([
                'payment_raw_request' => $paymentRequest,
                'raw_request_parsed' => $this->parseRawRequest($paymentRequest),
            ]);

            Log::info('Telebirr payment initiated', [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'payment_ref' => $order->reference
            ]);

            return response()->json([
                'success' => true,
                'payment_raw_request' => $paymentRequest,
                'order_id' => $order->id,
                'reference' => $order->reference
            ]);

        } catch (TelebirrException $e) {
            // 4. Handle payment initiation failure
            $order->update(['status' => 'failed']);

            Log::error('Telebirr payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed. Please try again.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Handle Telebirr webhooks
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();

            // Verify webhook and process payment
            $result = Telebirr::handleWebhook($request);

            if ($result && isset($payload['merch_order_id'])) {
                // Update order status based on payment result
                $order = Order::where('reference', $payload['merch_order_id'])->first();

                if ($order) {
                    if ($payload['trade_status'] === 'Completed') {
                        $order->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_details' => $payload
                        ]);

                        Log::info('Order marked as paid', [
                            'order_id' => $order->id,
                            'payment_ref' => $payload['merch_order_id']
                        ]);

                    } elseif ($payload['trade_status'] === 'Failed') {
                        $order->update([
                            'status' => 'failed',
                            'payment_details' => $payload
                        ]);

                        Log::warning('Payment failed', [
                            'order_id' => $order->id,
                            'payment_ref' => $payload['merch_order_id']
                        ]);
                    }
                }
            }

            return response()->json(['code' => 0, 'message' => 'Success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['code' => 1, 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * Payment success page
     */
    public function success(Order $order)
    {
        // Verify final payment status
        try {
            $result = Telebirr::verifyPayment($order->reference);

            if ($result && $result['order_status'] === 'PAY_SUCCESS') {
                $order->update([
                    'status' => 'paid',
                    'verified_at' => now()
                ]);

                return view('payment.success', compact('order'));
            } else {
                return view('payment.pending', compact('order'));
            }

        } catch (TelebirrException $e) {
            Log::error('Payment verification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return view('payment.error', [
                'order' => $order,
                'error' => 'Unable to verify payment status'
            ]);
        }
    }
}
```

## Troubleshooting

### Common Issues

#### RSA Key Format Error
```bash
# Ensure your private key includes proper PEM headers
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7VITN...
-----END PRIVATE KEY-----
```

#### Network Timeouts
```env
# Increase timeout in config or .env
TELEBIRR_API_TIMEOUT=30
```

#### Webhook Signature Verification Failures
```bash
# Test webhook locally first
php artisan telebirr:setup-webhook --url=https://your-domain.com/api/telebirr/webhook --test

# Check your public/private key pair
php artisan telebirr:test-connection
```

#### Multi-Merchant Configuration Issues
```bash
# Ensure merchant records exist
php artisan tinker
>>> App\Models\TelebirrMerchant::count()
>>> App\Models\TelebirrMerchant::first()

# Test with merchant context
Telebirr::initiatePayment($data, ['store_id' => 1])
```

#### Database Connection Issues
```bash
# Run migrations for multi-merchant mode
php artisan migrate
php artisan telebirr:install --run-migrations
```

#### Queue Configuration for Background Processing
```env
# Ensure queue is configured for payment verification
QUEUE_CONNECTION=database
TELEBIRR_QUEUE_VERIFY_PAYMENT_ENABLED=true
```

### Debug Mode
Enable detailed logging for troubleshooting:
```env
LOG_LEVEL=debug
TELEBIRR_LOGGING_ENABLED=true
```

### Getting Help
- Check logs: `storage/logs/laravel.log`
- Review network requests in browser dev tools
- Test API credentials manually with Postman
- Verify webhook endpoint is publicly accessible

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
- 🐛 Issues: [GitHub Issues](https://github.com/Matt-di/laravel-telebirr/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/Matt-di/laravel-telebirr/discussions)
