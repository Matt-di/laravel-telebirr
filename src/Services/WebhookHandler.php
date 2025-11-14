<?php

namespace Telebirr\LaravelTelebirr\Services;

use Illuminate\Http\Request;
use Telebirr\LaravelTelebirr\Contracts\WebhookHandlerInterface;
use Telebirr\LaravelTelebirr\Jobs\VerifyPaymentJob;
use Illuminate\Support\Facades\Log;

/**
 * Default webhook handler for Telebirr webhooks.
 *
 * This implementation provides basic webhook handling with payment verification.
 * Users can extend this class or implement WebhookHandlerInterface for custom logic.
 */
class WebhookHandler implements WebhookHandlerInterface
{
    /**
     * Handle an incoming webhook from Telebirr.
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $orderId = $payload['merch_order_id'] ?? null;
        $status = $payload['trade_status'] ?? null;

        Log::info('Processing Telebirr webhook', [
            'order_id' => $orderId,
            'status' => $status,
        ]);

        if (!$orderId) {
            Log::error('Missing order ID in webhook payload');
            return ['code' => '1', 'message' => 'Missing order ID'];
        }

        // Queue payment verification job if enabled
        if (config('telebirr.queue.verify_payment.enabled', true) && $status === 'Completed') {
            VerifyPaymentJob::dispatch(
                $orderId,
                $payload,
                $request->getClientIp()
            );

            Log::info('Queued payment verification job', ['order_id' => $orderId]);
        }

        return ['code' => '0', 'message' => 'Success'];
    }

    /**
     * Process webhook payload directly (for testing purposes).
     *
     * @param array $payload
     * @return mixed
     */
    public function processWebhook(array $payload)
    {
        // Create a mock request with the payload data
        $request = new Request();
        $request->merge($payload);

        return $this->handle($request);
    }
}
