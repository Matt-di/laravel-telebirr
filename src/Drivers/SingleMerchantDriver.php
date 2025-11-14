<?php

namespace Telebirr\LaravelTelebirr\Drivers;

use Telebirr\LaravelTelebirr\Services\SignatureService;

class SingleMerchantDriver implements DriverInterface
{
    /**
     * The signature service instance.
     *
     * @var SignatureService
     */
    protected SignatureService $signatureService;

    /**
     * Create a new single merchant driver instance.
     *
     * @param SignatureService $signatureService
     */
    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Get merchant configuration.
     *
     * For single merchant mode, we don't need complex context resolution.
     *
     * @param array $context
     * @return array
     */
    public function getMerchantConfig(array $context = []): array
    {
        return [
            'fabric_app_id' => config('telebirr.single_merchant.fabric_app_id'),
            'merchant_app_id' => config('telebirr.single_merchant.merchant_app_id'),
            'merchant_code' => config('telebirr.single_merchant.merchant_code'),
            'app_secret' => config('telebirr.single_merchant.app_secret'),
        ];
    }

    /**
     * Get fabric token for single merchant.
     *
     * @param array $context
     * @return string|null
     */
    public function getFabricToken(array $context = []): ?string
    {
        return $this->getMerchantConfig($context)['fabric_app_id'] ?? null;
    }

    /**
     * Get API keys for single merchant.
     *
     * @param array $context
     * @return array
     */
    public function getApiKeys(array $context = []): array
    {
        $config = config('telebirr.single_merchant');

        return [
            'fabric_app_id' => $config['fabric_app_id'] ?? null,
            'merchant_app_id' => $config['merchant_app_id'] ?? null,
            'merchant_code' => $config['merchant_code'] ?? null,
            'app_secret' => $config['app_secret'] ?? null,
            'rsa_private_key' => $config['rsa_private_key'] ?? null,
            'rsa_public_key' => $config['rsa_public_key'] ?? $this->signatureService->extractPublicKeyFromPrivateKey($config['rsa_private_key'] ?? ''),
        ];
    }

    /**
     * Validate that required configuration is present.
     *
     * @return bool
     */
    public function validateConfiguration(): bool
    {
        $keys = $this->getApiKeys();

        $requiredKeys = ['fabric_app_id', 'merchant_app_id', 'merchant_code', 'app_secret', 'rsa_private_key'];

        foreach ($requiredKeys as $key) {
            if (empty($keys[$key])) {
                return false;
            }
        }

        return true;
    }
}
