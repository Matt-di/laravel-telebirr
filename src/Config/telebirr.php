<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telebirr Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Telebirr payment gateway
    | integration. You can publish this file and customize it according
    | to your needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Mode Configuration
    |--------------------------------------------------------------------------
    |
    | Choose between 'single' or 'multi' merchant mode.
    | Single mode uses global config values, multi mode uses database-driven
    | merchant configurations.
    |
    */
    'mode' => env('TELEBIRR_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Base configuration for Telebirr API communication.
    |
    */
    'api' => [
        'base_url' => env('TELEBIRR_BASE_URL', 'https://developerportal.ethiotelebirr.et:38443/apiaccess/payment/gateway'),
        'timeout' => env('TELEBIRR_TIMEOUT', 60),
        'verify_ssl' => env('TELEBIRR_VERIFY_SSL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Single Merchant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for single merchant mode. Only used when mode is 'single'.
    |
    */
    'single_merchant' => [
        'fabric_app_id' => env('TELEBIRR_FABRIC_APP_ID'),
        'merchant_app_id' => env('TELEBIRR_MERCHANT_APP_ID'),
        'merchant_code' => env('TELEBIRR_MERCHANT_CODE'),
        'app_secret' => env('TELEBIRR_APP_SECRET'),
        'rsa_private_key' => env('TELEBIRR_RSA_PRIVATE_KEY'),
        'rsa_public_key' => env('TELEBIRR_RSA_PUBLIC_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Merchant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-merchant mode. Only used when mode is 'multi'.
    |
    */
    'merchant' => [
        'model' => \Telebirr\LaravelTelebirr\Models\Merchant::class,
        'table' => 'telebirr_merchants',
        'resolver' => \Telebirr\LaravelTelebirr\Services\MerchantResolver::class,
        'key_name' => env('TELEBIRR_MERCHANT_KEY_NAME', 'merchant_id'),
        'legacy_branch_support' => env('TELEBIRR_LEGACY_BRANCH_SUPPORT', true),
        'owner_mappings' => [
            'branch_id' => 'branch',
            'store_id' => 'store',
            'organization_id' => 'organization',
            'company_id' => 'company',
            'location_id' => 'location',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching fabric tokens and other temporary data.
    |
    */
    'cache' => [
        'tokens' => [
            'enabled' => env('TELEBIRR_CACHE_TOKENS', true),
            'ttl' => env('TELEBIRR_TOKEN_TTL', 3300), // 55 minutes
            'prefix' => env('TELEBIRR_CACHE_PREFIX', 'telebirr_token_'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling webhooks from Telebirr.
    |
    */
    'webhook' => [
        'handler' => \Telebirr\LaravelTelebirr\Services\WebhookHandler::class,
        'secret' => env('TELEBIRR_WEBHOOK_SECRET'),
        'signature_tolerance' => env('TELEBIRR_SIGNATURE_TOLERANCE', 300), // 5 minutes
        'path' => env('TELEBIRR_WEBHOOK_PATH', '/api/telebirr/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for queue-based payment verification.
    |
    */
    'queue' => [
        'verify_payment' => [
            'enabled' => env('TELEBIRR_QUEUE_VERIFICATION', true),
            'connection' => env('TELEBIRR_QUEUE_CONNECTION', 'database'),
            'queue' => env('TELEBIRR_QUEUE_NAME', 'telebirr'),
            'tries' => env('TELEBIRR_JOB_TRIES', 5),
            'timeout' => env('TELEBIRR_JOB_TIMEOUT', 120),
            'retry_schedule' => [5, 5, 5, 5, 5], // seconds between retries
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for package logging.
    |
    */
    'logging' => [
        'enabled' => env('TELEBIRR_LOGGING_ENABLED', true),
        'channel' => env('TELEBIRR_LOG_CHANNEL', 'default'),
        'sensitive_data' => env('TELEBIRR_LOG_SENSITIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Features
    |--------------------------------------------------------------------------
    |
    | Enable/disable various package features.
    |
    */
    'features' => [
        'routes' => env('TELEBIRR_ROUTES_ENABLED', true),
        'migrations' => env('TELEBIRR_MIGRATIONS_ENABLED', true),
        'commands' => env('TELEBIRR_COMMANDS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the models used by the package for custom implementations.
    |
    */
    'models' => [
        'user' => env('TELEBIRR_USER_MODEL', 'App\Models\User'),
        'payment' => env('TELEBIRR_PAYMENT_MODEL'),
        'invoice' => env('TELEBIRR_INVOICE_MODEL'),
        'order' => env('TELEBIRR_ORDER_MODEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events should be dispatched.
    |
    */
    'events' => [
        'payment_initiated' => \Telebirr\LaravelTelebirr\Events\PaymentInitiated::class,
        'payment_verified' => \Telebirr\LaravelTelebirr\Events\PaymentVerified::class,
        'webhook_received' => \Telebirr\LaravelTelebirr\Events\WebhookReceived::class,
    ],
];
