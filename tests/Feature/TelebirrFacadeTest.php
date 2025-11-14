<?php

namespace Telebirr\LaravelTelebirr\Tests\Feature;

use Telebirr\LaravelTelebirr\Telebirr as TelebirrFacade;
use Telebirr\LaravelTelebirr\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TelebirrFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable SSL verification and set timeouts for testing
        config([
            'telebirr.api.verify_ssl' => false,
            'telebirr.api.timeout' => 10,
            'telebirr.cache.tokens.enabled' => false, // Disable caching in tests
        ]);

        // Clear any cached tokens
        Cache::forget('telebirr_token_fabric_token_' . md5(serialize([])));
    }

    public function test_facade_returns_manager_instance()
    {
        $instance = TelebirrFacade::getFacadeRoot();

        $this->assertInstanceOf(\Telebirr\LaravelTelebirr\TelebirrManager::class, $instance);
    }

    public function test_can_access_telebirr_service_through_facade()
    {
        // Mock Telebirr API response for testing
        Http::fake([
            'test.telebirr.example.com/payment/v1/token' => Http::response([
                'token' => 'test_token_123'
            ], 200),
            'test.telebirr.example.com/payment/v1/merchant/preOrder' => Http::response([
                'biz_content' => [
                    'prepay_id' => 'TEST_PREPAY_123'
                ]
            ], 200),
        ]);

        $result = TelebirrFacade::initiatePayment([
            'txn_ref' => 'TEST_TXN_123',
            'amount' => 150.00,
            'subject' => 'Test Payment'
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContains('appid=test_merchant_app', $result);
    }

    public function test_facade_methods_exist()
    {
        // Test that all facade methods are available on the manager
        $manager = app(\Telebirr\LaravelTelebirr\TelebirrManager::class);

        $this->assertTrue(method_exists($manager, 'initiatePayment'));
        $this->assertTrue(method_exists($manager, 'verifyPayment'));
        $this->assertTrue(method_exists($manager, 'queryOrder'));
        $this->assertTrue(method_exists($manager, 'getAuthToken'));
        $this->assertTrue(method_exists($manager, 'handleWebhook'));
        $this->assertTrue(method_exists($manager, 'driver'));
    }

    public function test_driver_method_returns_driver_manager()
    {
        $driverResult = TelebirrFacade::driver();

        $this->assertInstanceOf(\Telebirr\LaravelTelebirr\Drivers\DriverInterface::class, $driverResult);
    }

    public function test_can_switch_drivers()
    {
        $singleDriver = TelebirrFacade::driver('single');
        $this->assertInstanceOf(\Telebirr\LaravelTelebirr\Drivers\SingleMerchantDriver::class, $singleDriver);

        // For multi-merchant, would need merchant resolver - just test the interface
        $multiDriver = TelebirrFacade::driver('multi');
        $this->assertInstanceOf(\Telebirr\LaravelTelebirr\Drivers\DriverInterface::class, $multiDriver);
    }

    public function test_payment_verification_with_mock()
    {
        // Mock API response for verification
        Http::fake([
            'test.telebirr.example.com/payment/v1/token' => Http::response([
                'token' => 'test_token_123'
            ], 200),
            'test.telebirr.example.com/v1/pay/query' => Http::response([
                'code' => '0',
                'data' => [
                    'order_status' => 'PAY_SUCCESS',
                    'total_amount' => '150.00'
                ]
            ], 200),
        ]);

        $result = TelebirrFacade::verifyPayment('TEST_TXN_123');

        $this->assertIsArray($result);
        $this->assertEquals('PAY_SUCCESS', $result['order_status']);
    }

    public function test_handles_api_errors_gracefully()
    {
        // Mock API failure
        Http::fake([
            'test.telebirr.example.com/payment/v1/token' => Http::response([
                'token' => 'test_token_123'
            ], 200),
            'test.telebirr.example.com/payment/v1/merchant/preOrder' => Http::response([
                'error' => 'Invalid merchant config'
            ], 400),
        ]);

        $result = TelebirrFacade::initiatePayment([
            'txn_ref' => 'TEST_TXN_123',
            'amount' => 150.00,
            'subject' => 'Test Payment'
        ]);

        // Should gracefully return null or empty on API error
        $this->assertNull($result);
    }

    public function test_context_passing_works()
    {
        // Test that merchant context is properly passed through
        // This would be more relevant in multi-merchant mode
        // For single merchant, context should be ignored

        Http::fake([
            'test.telebirr.example.com/payment/v1/token' => Http::response([
                'token' => 'test_token_123'
            ], 200),
            'test.telebirr.example.com/payment/v1/merchant/preOrder' => Http::response([
                'biz_content' => [
                    'prepay_id' => 'TEST_PREPAY_123'
                ]
            ], 200),
        ]);

        // Call with context (should work fine in single merchant mode)
        $result1 = TelebirrFacade::initiatePayment([
            'txn_ref' => 'TEST_TXN_123',
            'amount' => 150.00,
            'subject' => 'Test Payment'
        ], ['branch_id' => 1]);

        $result2 = TelebirrFacade::initiatePayment([
            'txn_ref' => 'TEST_TXN_456',
            'amount' => 150.00,
            'subject' => 'Test Payment'
        ], ['branch_id' => 2]);

        // Results should be the same since single merchant ignores context
        $this->assertEquals($result1, $result2);
    }
}
