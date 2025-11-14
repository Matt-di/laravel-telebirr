<?php

namespace Telebirr\LaravelTelebirr\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Telebirr\LaravelTelebirr\Services\TelebirrService;
use Telebirr\LaravelTelebirr\Jobs\VerifyPaymentJob;
use Telebirr\LaravelTelebirr\Events\PaymentInitiated;
use Telebirr\LaravelTelebirr\Events\WebhookReceived;

class TelebirrController extends Controller
{
    /**
     * The Telebirr service instance.
     *
     * @var TelebirrService
     */
    protected TelebirrService $telebirrService;

    /**
     * Create a new controller instance.
     *
     * @param TelebirrService $telebirrService
     */
    public function __construct(TelebirrService $telebirrService)
    {
        $this->telebirrService = $telebirrService;
    }

    /**
     * Initiate a payment order.
     *
     * @param \Telebirr\LaravelTelebirr\Http\Requests\CreateOrderRequest $request
     * @return JsonResponse
     */
    public function createOrder(Request $request): JsonResponse
    {
        // For now, using basic validation - will create form requests next
        $validated = $request->validate([
            'invoice_id' => 'required|string',
            'subject' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'return_url' => 'nullable|url',
            'merchant_context' => 'nullable|array',
        ]);

        try {
            $orderData = [
                'txn_ref' => $validated['invoice_id'],
                'amount' => $validated['amount'],
                'subject' => $validated['subject'],
            ];

            $rawRequest = $this->telebirrService->createPreorder($orderData);

            if (!$rawRequest) {
                Log::error('Telebirr payment initiation failed', [
                    'invoice_id' => $validated['invoice_id'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Telebirr payment order'
                ], 500);
            }

            // Dispatch payment initiated event
            PaymentInitiated::dispatch($validated['invoice_id'], $orderData, $validated['merchant_context'] ?? []);

            Log::info('Telebirr payment initiation successful', [
                'invoice_id' => $validated['invoice_id'],
                'raw_request_length' => strlen($rawRequest),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_id' => $validated['invoice_id'],
                    'raw_request' => $rawRequest,
                    'amount' => $validated['amount'],
                    'subject' => $validated['subject'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Telebirr payment initiation exception', [
                'error' => $e->getMessage(),
                'invoice_id' => $validated['invoice_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }

    /**
     * Handle payment verification.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'merchant_context' => 'nullable|array',
        ]);

        try {
            $result = $this->telebirrService->verifyPayment($validated['transaction_id']);

            return response()->json([
                'success' => $result !== null,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'transaction_id' => $validated['transaction_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed'
            ], 500);
        }
    }

    /**
     * Query an order status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function queryOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'merchant_context' => 'nullable|array',
        ]);

        try {
            $result = $this->telebirrService->queryOrder($validated['order_id']);

            return response()->json([
                'success' => $result !== null,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Order query failed', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Query failed'
            ], 500);
        }
    }

    /**
     * Get authentication token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
            'merchant_context' => 'nullable|array',
        ]);

        try {
            $result = $this->telebirrService->getAuthToken($validated['access_token']);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Auth token request failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Handle webhook from Telebirr.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        // Log webhook receipt
        Log::info('Telebirr webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // Dispatch webhook received event
        WebhookReceived::dispatch($request->all());

        // Verify webhook signature if configured
        if (config('telebirr.webhook.secret')) {
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature', [
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'code' => '1',
                    'message' => 'Invalid signature'
                ], 401);
            }
        }

        // Queue payment verification job
        if (config('telebirr.queue.verify_payment.enabled', true)) {
            VerifyPaymentJob::dispatch(
                $request->input('merch_order_id'),
                $request->all(),
                $request->getClientIp()
            );
        } else {
            // Handle synchronously if queue disabled
            $this->processWebhookSync($request);
        }

        return response()->json([
            'code' => '0',
            'message' => 'Success'
        ]);
    }

    /**
     * Process webhook synchronously (fallback).
     *
     * @param Request $request
     * @return void
     */
    protected function processWebhookSync(Request $request): void
    {
        $orderId = $request->input('merch_order_id');
        $status = $request->input('trade_status');

        if (!$orderId) {
            Log::error('Missing order ID in webhook');
            return;
        }

        if ($status === 'Completed') {
            $result = $this->telebirrService->verifyPayment($orderId);

            if ($result) {
                Log::info('Webhook payment verified', [
                    'order_id' => $orderId,
                    'result' => $result,
                ]);
            } else {
                Log::warning('Webhook payment verification failed', [
                    'order_id' => $orderId,
                ]);
            }
        }
    }

    /**
     * Verify webhook signature.
     *
     * @param Request $request
     * @return bool
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Telebirr-Signature');
        $timestamp = $request->header('X-Telebirr-Timestamp');
        $payload = $request->getContent();

        if (!$signature || !$timestamp || !$payload) {
            return false;
        }

        // Check timestamp tolerance
        $tolerance = config('telebirr.webhook.signature_tolerance', 300);
        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $secret = config('telebirr.webhook.secret');
        $expectedSignature = hash_hmac('sha256', $payload . $timestamp, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
