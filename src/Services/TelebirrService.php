<?php

namespace Telebirr\LaravelTelebirr\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Telebirr\LaravelTelebirr\Drivers\DriverInterface;
use Telebirr\LaravelTelebirr\Exceptions\TelebirrException;

class TelebirrService
{
    /**
     * The driver instance.
     *
     * @var DriverInterface
     */
    protected DriverInterface $driver;

    /**
     * The signature service instance.
     *
     * @var SignatureService
     */
    protected SignatureService $signatureService;

    /**
     * Context data for merchant resolution.
     *
     * @var array
     */
    protected array $context;

    /**
     * Create a new Telebirr service instance.
     *
     * @param DriverInterface $driver
     * @param SignatureService $signatureService
     * @param array $context
     */
    public function __construct(
        DriverInterface $driver,
        SignatureService $signatureService,
        array $context = []
    ) {
        $this->driver = $driver;
        $this->signatureService = $signatureService;
        $this->context = $context;
    }

    /**
     * Get fabric token for the current merchant.
     *
     * @return string|null
     */
    public function getFabricToken(): ?string
    {
        // Check cache first
        if (config('telebirr.cache.tokens.enabled', true)) {
            $cacheKey = $this->getCacheKey('fabric_token');

            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
        }

        $keys = $this->driver->getApiKeys($this->context);

        if (!$keys['fabric_app_id'] || !$keys['app_secret']) {
            $this->log('error', 'Telebirr fabric token keys missing', [
                'fabric_app_id' => $keys['fabric_app_id'] ? '***' : null,
                'app_secret' => $keys['app_secret'] ? '***' : null,
            ]);
            return null;
        }

        $this->log('info', 'Telebirr fabric token request', [
            'url' => "{$this->getBaseUrl()}/payment/v1/token",
        ]);

        try {
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-APP-Key' => $keys['fabric_app_id']
                ])
                ->post("{$this->getBaseUrl()}/payment/v1/token", [
                    'appSecret' => $keys['app_secret']
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['token'])) {
                // Cache the token
                if (config('telebirr.cache.tokens.enabled', true)) {
                    Cache::put(
                        $this->getCacheKey('fabric_token'),
                        $data['token'],
                        config('telebirr.cache.tokens.ttl', 3300)
                    );
                }

                return $data['token'];
            }

            $this->log('error', 'Telebirr fabric token request failed', ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            $this->log('error', 'Telebirr fabric token request exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a preorder with Telebirr.
     *
     * @param array $orderData
     * @return string|null
     */
    public function createPreorder(array $orderData): ?string
    {
        $fabricToken = $this->getFabricToken();

        if (!$fabricToken) {
            return null;
        }

        $payload = $this->createRequestObject($orderData);

        $this->log('info', 'Creating Telebirr preorder', [
            'order_data' => $orderData,
        ]);

        try {
            $keys = $this->driver->getApiKeys($this->context);
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    "X-APP-Key" => $keys['fabric_app_id'],
                    "Authorization" => $fabricToken
                ])
                ->post("{$this->getBaseUrl()}/payment/v1/merchant/preOrder", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['biz_content']['prepay_id'])) {
                $prepayId = $data['biz_content']['prepay_id'];
                return $this->createRawRequest($prepayId);
            }

            $this->log('error', 'Telebirr preorder failed', [
                'payload' => $payload,
                'response' => $data
            ]);
            return null;
        } catch (\Exception $e) {
            $this->log('error', 'Telebirr preorder exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify a payment with Telebirr.
     *
     * @param string $outTradeNo
     * @return array|null
     */
    public function verifyPayment(string $outTradeNo): ?array
    {
        $fabricToken = $this->getFabricToken();

        if (!$fabricToken) {
            return null;
        }

        $keys = $this->driver->getApiKeys($this->context);
        $nonce = $this->signatureService->generateNonce();
        $timestamp = $this->signatureService->generateTimestamp();

        $payload = [
            'merchantAppId' => $keys['merchant_app_id'],
            'outTradeNo' => $outTradeNo,
            'nonce' => $nonce,
            'timestamp' => $timestamp,
        ];

        $signature = $this->signatureService->signRequest($payload, $keys['rsa_private_key']);
        $payload['sign'] = $signature;

        try {
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    "Content-Type: application/json",
                    "X-APP-Key: " . $keys['fabric_app_id'],
                    "Authorization: " . $fabricToken
                ])
                ->post("{$this->getBaseUrl()}/v1/pay/query", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['code']) && $data['code'] === '0') {
                return $data['data'];
            }

            $this->log('error', 'Telebirr verify payment failed', [
                'outTradeNo' => $outTradeNo,
                'response' => $data
            ]);
            return null;
        } catch (\Exception $e) {
            $this->log('error', 'Telebirr verify payment exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Query an order status with Telebirr.
     *
     * @param string $merchOrderId
     * @return array|null
     */
    public function queryOrder(string $merchOrderId): ?array
    {
        $fabricToken = $this->getFabricToken();

        if (!$fabricToken) {
            return null;
        }

        $keys = $this->driver->getApiKeys($this->context);

        $bizContent = [
            'appid' => $keys['merchant_app_id'],
            'merch_code' => $keys['merchant_code'],
            'merch_order_id' => $merchOrderId,
        ];

        $payload = [
            'nonce_str' => $this->signatureService->generateNonce(),
            'method' => 'payment.queryorder',
            'timestamp' => $this->signatureService->generateTimestamp(),
            'version' => '1.0',
            'biz_content' => $bizContent
        ];

        $payload['sign'] = $this->signatureService->signRequest($payload, $keys['rsa_private_key']);
        $payload['sign_type'] = 'SHA256WithRSA';

        try {
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-APP-Key' => $keys['fabric_app_id'],
                    'Authorization' => $fabricToken
                ])
                ->post("{$this->getBaseUrl()}/payment/v1/merchant/queryOrder", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['result']) && $data['result'] === 'SUCCESS') {
                return $data['biz_content'];
            }

            $this->log('error', 'Telebirr query order failed', [
                'merchOrderId' => $merchOrderId,
                'response' => $data
            ]);
            return null;
        } catch (\Exception $e) {
            $this->log('error', 'Telebirr query order exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get auth token from Telebirr.
     *
     * @param string $accessToken
     * @return array|null
     */
    public function getAuthToken(string $accessToken): ?array
    {
        $keys = $this->driver->getApiKeys($this->context);

        $fabricAppId = $keys['fabric_app_id'];
        $appSecret = $keys['app_secret'];
        $rsaPrivateKey = $keys['rsa_private_key'];

        if (!$fabricAppId || !$appSecret) {
            $this->log('error', 'Telebirr auth config missing', [
                'fabric_app_id' => $fabricAppId ? '***' : null,
                'app_secret' => $appSecret ? '***' : null,
            ]);
            return null;
        }

        // Get fabric token using config values
        $fabricToken = $this->getFabricTokenFromConfig();

        if (!$fabricToken) {
            return null;
        }

        $payload = $this->createAuthTokenRequestObject($accessToken);

        $this->log('info', 'Telebirr auth token request', [
            'access_token' => substr($accessToken, 0, 10) . '***'
        ]);

        try {
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-APP-Key' => $fabricAppId,
                    'Authorization' => $fabricToken
                ])
                ->post("{$this->getBaseUrl()}/payment/v1/auth/authToken", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['code']) && $data['code'] === '0') {
                $this->log('info', 'Telebirr auth token success', [
                    'open_id' => $data['biz_content']['open_id'] ?? null,
                    'has_personal_info' => isset($data['biz_content']['nickName'])
                ]);
                return $data['biz_content'];
            }

            $this->log('error', 'Telebirr auth token failed', ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            $this->log('error', 'Telebirr auth token exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create request object for preorder.
     *
     * @param array $orderData
     * @return array
     */
    private function createRequestObject(array $orderData): array
    {
        $keys = $this->driver->getApiKeys($this->context);

        $bizContent = [
            'notify_url' => config('telebirr.webhook.path', route('telebirr.webhook')),
            'business_type' => 'BuyGoods',
            'trade_type' => 'InApp',
            'appid' => $keys['merchant_app_id'],
            'merch_code' => $keys['merchant_code'],
            'merch_order_id' => str_replace('-', '', $orderData['txn_ref']),
            'title' => preg_replace('/[~`!#$%^*()\-+=|\/<>?;:"\[\]{}\\\]/', '', $orderData['subject']),
            'total_amount' => (string) $orderData['amount'],
            'trans_currency' => 'ETB',
            'timeout_express' => '120m',
            'payee_identifier' => $keys['merchant_code'],
            'payee_identifier_type' => '04',
            'payee_type' => '5000',
        ];

        $req = [
            'nonce_str' => $this->signatureService->generateNonce(),
            'method' => 'payment.preorder',
            'timestamp' => $this->signatureService->generateTimestamp(),
            'version' => '1.0',
            'biz_content' => $bizContent
        ];

        $req['sign'] = $this->signatureService->signRequest($req, $keys['rsa_private_key']);
        $req['sign_type'] = 'SHA256WithRSA';

        return $req;
    }

    /**
     * Create raw request string for mobile app.
     *
     * @param string $prepayId
     * @return string
     */
    private function createRawRequest(string $prepayId): string
    {
        $keys = $this->driver->getApiKeys($this->context);

        $maps = [
            "appid" => $keys['merchant_app_id'],
            "merch_code" => $keys['merchant_code'],
            "nonce_str" => $this->signatureService->generateNonce(),
            "prepay_id" => $prepayId,
            "timestamp" => $this->signatureService->generateTimestamp(),
            "sign_type" => "SHA256WithRSA"
        ];

        $sign = $this->signatureService->signRequest($maps, $keys['rsa_private_key']);
        $maps['sign'] = $sign;

        // Build query string like ET_DEMO
        $rawRequest = '';
        foreach ($maps as $key => $value) {
            $rawRequest .= $key . '=' . $value . '&';
        }

        return rtrim($rawRequest, '&');
    }

    /**
     * Create auth token request object.
     *
     * @param string $accessToken
     * @return array
     */
    private function createAuthTokenRequestObject(string $accessToken): array
    {
        $keys = $this->driver->getApiKeys($this->context);
        $merchanAppId = $keys['merchant_app_id'];

        $bizContent = [
            'access_token' => $accessToken,
            'trade_type' => 'InApp',
            'appid' => $merchanAppId,
            'resource_type' => 'OpenId',
        ];

        $req = [
            'nonce_str' => $this->signatureService->generateNonce(),
            'method' => 'payment.authtoken',
            'timestamp' => $this->signatureService->generateTimestamp(),
            'version' => '1.0',
            'biz_content' => $bizContent
        ];

        $req['sign'] = $this->signatureService->signRequest($req, $keys['rsa_private_key']);
        $req['sign_type'] = 'SHA256WithRSA';

        return $req;
    }

    /**
     * Get fabric token using config values (for auth).
     *
     * @return string|null
     */
    private function getFabricTokenFromConfig(): ?string
    {
        $keys = $this->driver->getApiKeys($this->context);
        $fabricAppId = $keys['fabric_app_id'];
        $appSecret = $keys['app_secret'];

        if (!$fabricAppId || !$appSecret) {
            return null;
        }

        try {
            $response = Http::retry(3, 100)
                ->withOptions([
                    'verify' => config('telebirr.api.verify_ssl', false),
                    'timeout' => config('telebirr.api.timeout', 60),
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-APP-Key' => $fabricAppId
                ])
                ->post("{$this->getBaseUrl()}/payment/v1/token", [
                    'appSecret' => $appSecret
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['token'])) {
                return $data['token'];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the base URL for Telebirr API.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return config('telebirr.api.base_url');
    }

    /**
     * Get cache key with prefix.
     *
     * @param string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        $prefix = config('telebirr.cache.tokens.prefix', 'telebirr_token_');
        $contextHash = md5(serialize($this->context));
        return $prefix . $key . '_' . $contextHash;
    }

    /**
     * Log a message with context.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (!config('telebirr.logging.enabled', true)) {
            return;
        }

        $logger = Log::channel(config('telebirr.logging.channel', 'default'));

        if (!config('telebirr.logging.sensitive_data', false)) {
            // Remove sensitive data
            $context = $this->sanitizeLogData($context);
        }

        $logger->$level($message, $context);
    }

    /**
     * Sanitize sensitive data from logs.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'rsa_private_key'];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeLogData($value);
            }
        }

        return $data;
    }
}
