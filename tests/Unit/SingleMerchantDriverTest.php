<?php

namespace Telebirr\LaravelTelebirr\Tests\Unit;

use Telebirr\LaravelTelebirr\Drivers\SingleMerchantDriver;
use Telebirr\LaravelTelebirr\Services\SignatureService;
use Telebirr\LaravelTelebirr\Tests\TestCase;

class SingleMerchantDriverTest extends TestCase
{
    private SingleMerchantDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $signatureService = app(SignatureService::class);
        $this->driver = new SingleMerchantDriver($signatureService);
    }

    public function test_get_api_keys_from_config()
    {
        $keys = $this->driver->getApiKeys();

        $this->assertIsArray($keys);
        $this->assertArrayHasKey('fabric_app_id', $keys);
        $this->assertArrayHasKey('merchant_app_id', $keys);
        $this->assertArrayHasKey('merchant_code', $keys);
        $this->assertArrayHasKey('app_secret', $keys);
        $this->assertArrayHasKey('rsa_private_key', $keys);
    }

    public function test_config_validation_passes_with_valid_config()
    {
        // With the test config already set up in TestCase, this should pass
        $isValid = $this->driver->validateConfiguration();

        $this->assertTrue($isValid);
    }

    public function test_config_validation_fails_with_missing_keys()
    {
        // Temporarily modify config to remove required keys
        config(['telebirr.single_merchant.fabric_app_id' => null]);

        $isValid = $this->driver->validateConfiguration();

        $this->assertFalse($isValid);

        // Restore config for other tests
        config(['telebirr.single_merchant.fabric_app_id' => 'test_fabric_app']);
    }

    public function test_get_merchant_config_returns_basic_info()
    {
        $config = $this->driver->getMerchantConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('fabric_app_id', $config);
        $this->assertArrayHasKey('merchant_app_id', $config);
        $this->assertArrayHasKey('merchant_code', $config);
        $this->assertArrayHasKey('app_secret', $config);
    }

    public function test_get_fabric_token_returns_config_value()
    {
        $token = $this->driver->getFabricToken();

        $this->assertEquals('test_fabric_app', $token);
    }

    public function test_context_parameter_is_ignored()
    {
        // Single merchant driver should ignore context (since it's single merchant)
        $keys1 = $this->driver->getApiKeys([]);
        $keys2 = $this->driver->getApiKeys(['branch_id' => 1]);

        // Should return same results regardless of context
        $this->assertEquals($keys1, $keys2);
    }
}
