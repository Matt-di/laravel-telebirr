<?php

namespace Telebirr\LaravelTelebirr\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Telebirr\LaravelTelebirr\TelebirrServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup default config for test bench
        $app['config']->set('telebirr', [
            'mode' => 'single',
            'api' => [
                'base_url' => 'https://test.telebirr.example.com',
                'timeout' => 30,
                'verify_ssl' => false,
            ],
            'single_merchant' => [
                'fabric_app_id' => 'test_fabric_app',
                'merchant_app_id' => 'test_merchant_app',
                'merchant_code' => 'TEST123',
                'app_secret' => 'test_app_secret',
                'rsa_private_key' => "-----BEGIN PRIVATE KEY-----\ntest_private_key_content\n-----END PRIVATE KEY-----",
                'rsa_public_key' => "-----BEGIN PUBLIC KEY-----\ntest_public_key_content\n-----END PUBLIC KEY-----",
            ],
            'webhook' => [
                'path' => '/api/telebirr/webhook',
                'secret' => null,
                'signature_tolerance' => 300,
            ],
            'queue' => [
                'verify_payment' => [
                    'enabled' => true,
                    'tries' => 3,
                    'timeout' => 60,
                    'retry_schedule' => [5, 5, 5],
                ],
            ],
            'cache' => [
                'tokens' => [
                    'enabled' => false, // Disable caching in tests
                    'ttl' => 300,
                ],
            ],
            'logging' => [
                'enabled' => false, // Disable logging in tests
                'channel' => 'default',
            ],
            'routes' => [
                'enabled' => true,
            ],
            'migrations' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     * @return array<int, string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TelebirrServiceProvider::class,
        ];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Publish and run migrations for tests
        Artisan::call('vendor:publish', [
            '--provider' => 'Telebirr\LaravelTelebirr\TelebirrServiceProvider',
            '--tag' => 'telebirr-migrations',
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../src/Database/Migrations');
    }

    /**
     * Get the Telebirr facade alias.
     *
     * @return array<string, string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Telebirr' => \Telebirr\LaravelTelebirr\Telebirr::class,
        ];
    }
}
