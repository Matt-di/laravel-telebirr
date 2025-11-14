<?php

namespace Telebirr\LaravelTelebirr\Tests\Feature;

use Telebirr\LaravelTelebirr\Events\WebhookReceived;
use Telebirr\LaravelTelebirr\Jobs\VerifyPaymentJob;
use Telebirr\LaravelTelebirr\Services\WebhookHandler;
use Telebirr\LaravelTelebirr\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class TelebirrIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure for testing
        config([
            'telebirr.queue.verify_payment.enabled' => true,
            'telebirr.logging.enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_webhook_processing_with_valid_payload()
    {
        // Test webhook handler with a complete payload
        $handler = new WebhookHandler();

        $webhookData = [
            'merch_order_id' => 'TXN_TEST_WEB_001',
            'trade_status' => 'Completed',
            'total_amount' => '299.00',
            'trade_no' => 'TELEBIRR_TRADE_123',
            'timestamp' => time(),
        ];

        $result = $handler->processWebhook($webhookData);

        // Verify webhook result
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code'] ?? null);
        $this->assertEquals('Success', $result['message'] ?? null);
    }

    public function test_webhook_processing_dispatches_job()
    {
        Queue::fake();
        Event::fake();

        $handler = new WebhookHandler();

        // Process webhook payload with "Completed" status
        $webhookData = [
            'merch_order_id' => 'TXN_JOB_TEST_001',
            'trade_status' => 'Completed',
            'total_amount' => '299.00',
            'trade_no' => 'TELEBIRR_TRADE_123',
            'timestamp' => time(),
        ];

        $result = $handler->processWebhook($webhookData);

        // For "Completed" status, verify payment job should be dispatched
        Queue::assertPushed(VerifyPaymentJob::class, function ($job) use ($webhookData) {
            return $job->transactionId === $webhookData['merch_order_id'] &&
                   $job->payload === $webhookData;
        });
    }

    public function test_webhook_processing_with_missing_order_id()
    {
        $handler = new WebhookHandler();

        // Payload missing order ID
        $webhookData = [
            'trade_status' => 'Completed',
            'total_amount' => '100.00',
        ];

        $result = $handler->processWebhook($webhookData);

        // Should return error for missing order ID
        $this->assertIsArray($result);
        $this->assertEquals('1', $result['code'] ?? null);
        $this->assertEquals('Missing order ID', $result['message'] ?? null);
    }

    public function test_webhook_processing_with_pending_status()
    {
        Queue::fake();

        $handler = new WebhookHandler();

        // Process webhook with "Pending" status (should not dispatch job)
        $webhookData = [
            'merch_order_id' => 'TXN_PENDING_TEST_001',
            'trade_status' => 'Pending',
            'total_amount' => '199.00',
            'trade_no' => 'TELEBIRR_PENDING_123',
            'timestamp' => time(),
        ];

        $result = $handler->processWebhook($webhookData);

        // Verify success but no job dispatched for "Pending" status
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code'] ?? null);
        Queue::assertNotPushed(VerifyPaymentJob::class);
    }

    public function test_webhook_processing_with_custom_payload()
    {
        $handler = new WebhookHandler();

        // Test with minimal required fields
        $webhookData = [
            'merch_order_id' => 'TXN_MINIMAL_TEST',
            'trade_status' => 'Failed',
        ];

        $result = $handler->processWebhook($webhookData);

        // Should handle minimal payload
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code'] ?? null);

        // Failed status should not dispatch job
        Queue::fake();
        Queue::assertNotPushed(VerifyPaymentJob::class);
    }

    public function test_webhook_processing_with_long_order_id()
    {
        $handler = new WebhookHandler();

        // Test with long order ID
        $longOrderId = 'TXN_VERY_LONG_ORDER_ID_TEST_001_2023_09_15_123456789';
        $webhookData = [
            'merch_order_id' => $longOrderId,
            'trade_status' => 'Completed',
            'total_amount' => '999.99',
            'trade_no' => 'TELEBIRR_LONG_123',
            'timestamp' => time(),
        ];

        $result = $handler->processWebhook($webhookData);

        // Should handle long order IDs
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code'] ?? null);
        $this->assertEquals('Success', $result['message'] ?? null);

        // Job should still be dispatched
        Queue::fake();
        Queue::assertPushed(VerifyPaymentJob::class, function ($job) use ($longOrderId) {
            return $job->transactionId === $longOrderId;
        });
    }

    public function test_webhook_logging_integration()
    {
        Log::spy();

        $handler = new WebhookHandler();

        $webhookData = [
            'merch_order_id' => 'TXN_LOGGING_TEST',
            'trade_status' => 'Completed',
            'total_amount' => '150.00',
            'trade_no' => 'TELEBIRR_LOG_123',
            'timestamp' => time(),
        ];

        $handler->processWebhook($webhookData);

        // Verify logging was called with correct order ID and status
        Log::shouldHaveReceived('info', function ($level, $message, $context) use ($webhookData) {
            return strpos($message, 'Processing Telebirr webhook') === 0 &&
                   isset($context['order_id']) &&
                   $context['order_id'] === $webhookData['merch_order_id'] &&
                   isset($context['status']) &&
                   $context['status'] === $webhookData['trade_status'];
        });
    }

    public function test_webhook_processing_multiple_calls_sequential()
    {
        $handler = new WebhookHandler();
        Queue::fake();

        $webhooks = [
            [
                'merch_order_id' => 'TXN_MULTI_001',
                'trade_status' => 'Completed',
                'total_amount' => '100.00',
                'trade_no' => 'TELEBIRR_MULTI_001',
                'timestamp' => time(),
            ],
            [
                'merch_order_id' => 'TXN_MULTI_002',
                'trade_status' => 'Completed',
                'total_amount' => '200.00',
                'trade_no' => 'TELEBIRR_MULTI_002',
                'timestamp' => time(),
            ],
        ];

        foreach ($webhooks as $webhookData) {
            $result = $handler->processWebhook($webhookData);
            $this->assertIsArray($result);
            $this->assertEquals('0', $result['code'] ?? null);
        }

        // Should have dispatched 2 jobs total
        Queue::assertPushed(VerifyPaymentJob::class, 2);
    }

    public function test_webhook_handler_configuration_toggling()
    {
        // Disable job dispatching in config
        config(['telebirr.queue.verify_payment.enabled' => false]);
        Queue::fake();

        $handler = new WebhookHandler();

        $webhookData = [
            'merch_order_id' => 'TXN_DISABLED_JOB',
            'trade_status' => 'Completed',
            'total_amount' => '75.00',
            'trade_no' => 'TELEBIRR_DISABLED_123',
            'timestamp' => time(),
        ];

        $result = $handler->processWebhook($webhookData);

        // Should still process but not dispatch job when disabled
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code'] ?? null);
        Queue::assertNotPushed(VerifyPaymentJob::class);
    }

    public function test_webhook_processing_status_based_job_dispatch()
    {
        $handler = new WebhookHandler();

        // Test different statuses affect job dispatching
        $testStatuses = [
            'Completed' => true,  // Should dispatch job
            'Pending' => false,   // Should not dispatch job
            'Failed' => false,    // Should not dispatch job
        ];

        foreach ($testStatuses as $status => $shouldDispatch) {
            Queue::fake();

            $webhookData = [
                'merch_order_id' => 'TXN_STATUS_TEST_' . $status,
                'trade_status' => $status,
                'total_amount' => '100.00',
                'trade_no' => 'TELEBIRR_STATUS_123',
                'timestamp' => time(),
            ];

            $result = $handler->processWebhook($webhookData);

            // Should process all statuses without error
            $this->assertIsArray($result);
            $this->assertEquals('0', $result['code'] ?? null);

            // Verify job dispatching based on status
            if ($shouldDispatch) {
                Queue::assertPushed(VerifyPaymentJob::class, function ($job) use ($webhookData) {
                    return $job->transactionId === $webhookData['merch_order_id'];
                });
            } else {
                Queue::assertNotPushed(VerifyPaymentJob::class);
            }
        }
    }
}
