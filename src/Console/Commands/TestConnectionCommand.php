<?php

namespace Telebirr\LaravelTelebirr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telebirr\LaravelTelebirr\Services\TelebirrService;
use Telebirr\LaravelTelebirr\Drivers\DriverInterface;

/**
 * Test connection to Telebirr API.
 */
class TestConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telebirr:test-connection
                            {--fabric-token : Test fabric token retrieval only}
                            {--auth-token : Test auth token retrieval}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Telebirr API services';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔌 Testing Telebirr API connection');
        $this->newLine();

        $mode = config('telebirr.mode', 'single');
        $this->info("Mode: {$mode}");

        // Get the driver and check configuration
        $driver = app(DriverInterface::class);
        $this->validateConfiguration($driver);

        // Test fabric token
        if ($this->option('fabric-token')) {
            return $this->testFabricTokenOnly($driver);
        }

        // Test full connection
        $this->testFullConnection($driver);

        if ($this->option('auth-token')) {
            $this->testAuthToken($driver);
        }

        return self::SUCCESS;
    }

    /**
     * Validate driver configuration.
     *
     * @param DriverInterface $driver
     * @return void
     */
    protected function validateConfiguration(DriverInterface $driver): void
    {
        $this->info('⚙️ Validating configuration...');

        try {
            $apiKeys = $driver->getApiKeys();

            // Check required keys
            $required = ['fabric_app_id', 'merchant_app_id', 'merchant_code', 'app_secret', 'rsa_private_key'];
            $missing = [];

            foreach ($required as $key) {
                if (empty($apiKeys[$key])) {
                    $missing[] = $key;
                }
            }

            if (!empty($missing)) {
                $this->error('❌ Missing required configuration:');
                foreach ($missing as $key) {
                    $this->error("  • {$key}");
                }

                $this->newLine();
                $this->error('Please check your .env file or config/telebirr.php');
                exit(1);
            }

            $this->info('✅ Configuration is valid');
            if ($this->option('verbose')) {
                $this->line('Fabric App ID: ' . substr($apiKeys['fabric_app_id'], 0, 8) . '...');
                $this->line('Merchant App ID: ' . substr($apiKeys['merchant_app_id'], 0, 8) . '...');
                $this->line('Merchant Code: ' . $apiKeys['merchant_code']);
            }

        } catch (\Exception $e) {
            $this->error('❌ Configuration error: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Test fabric token retrieval only.
     *
     * @param DriverInterface $driver
     * @return int
     */
    protected function testFabricTokenOnly(DriverInterface $driver): int
    {
        $this->info('🔑 Testing fabric token retrieval...');

        try {
            // Clear any cached token for fresh test
            $cacheKey = 'telebirr_token_fabric_token_' . md5(serialize([]));
            Cache::forget($cacheKey);

            $service = app(TelebirrService::class);
            $token = $service->getFabricToken();

            if ($token) {
                $this->info('✅ Fabric token retrieved successfully');

                if ($this->option('verbose')) {
                    $this->line('Token: ' . substr($token, 0, 20) . '...');
                    $expiresIn = config('telebirr.cache.tokens.ttl', 3300);
                    $this->line("Cached for: {$expiresIn} seconds (" . round($expiresIn / 60, 1) . " minutes)");
                }

                return self::SUCCESS;
            } else {
                $this->error('❌ Failed to retrieve fabric token');
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Fabric token test failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Test full API connection.
     *
     * @param DriverInterface $driver
     * @return void
     */
    protected function testFullConnection(DriverInterface $driver): void
    {
        $this->testFabricTokenOnly($driver);
        $this->newLine();

        // Test a queryOrder call with a dummy transaction
        $this->info('🔍 Testing API connectivity with queryOrder...');

        try {
            $service = app(TelebirrService::class);
            $result = $service->queryOrder('TEST_' . time());

            // We expect this to fail gracefully (transaction doesn't exist)
            // but it should make the API call successfully
            $this->info('✅ API connectivity test successful');

            if ($this->option('verbose') && $result) {
                $this->line('Response: ' . json_encode($result, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            // Check if it's a connectivity issue vs expected API error
            if (str_contains($e->getMessage(), 'cURL error') ||
                str_contains($e->getMessage(), 'Connection refused') ||
                str_contains($e->getMessage(), 'timeout')) {
                $this->error('❌ API connectivity failed: ' . $e->getMessage());
                $this->newLine();
                $this->error('Please check:');
                $this->error('1. Internet connection');
                $this->error('2. API base URL configuration');
                $this->error('3. Proxy/firewall settings');
            } else {
                $this->info('✅ API connectivity test successful (expected error for non-existent transaction)');
                if ($this->option('verbose')) {
                    $this->line('Error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Test auth token retrieval.
     *
     * @param DriverInterface $driver
     * @return void
     */
    protected function testAuthToken(DriverInterface $driver): void
    {
        $this->newLine();
        $this->info('👤 Testing auth token retrieval...');

        $accessToken = $this->ask('Enter an access token from Telebirr app');

        if (!$accessToken) {
            $this->warn('No access token provided, skipping auth token test');
            return;
        }

        try {
            $service = app(TelebirrService::class);
            $result = $service->getAuthToken($accessToken);

            if ($result) {
                $this->info('✅ Auth token retrieved successfully');

                if ($this->option('verbose')) {
                    $this->line('User ID: ' . ($result['open_id'] ?? 'N/A'));
                    $this->line('Identifier: ' . ($result['identifier'] ?? 'N/A'));
                    $this->line('Has personal info: ' . (isset($result['nickName']) ? 'Yes' : 'No'));
                }
            } else {
                $this->error('❌ Auth token retrieval failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Auth token test failed: ' . $e->getMessage());
        }
    }
}
