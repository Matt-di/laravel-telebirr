<?php

namespace Telebirr\LaravelTelebirr\Drivers;

use Telebirr\LaravelTelebirr\Contracts\MerchantResolverInterface;
use Telebirr\LaravelTelebirr\Services\SignatureService;
use Telebirr\LaravelTelebirr\Exceptions\TelebirrException;

class MultiMerchantDriver implements DriverInterface
{
    /**
     * The merchant resolver instance.
     *
     * @var MerchantResolverInterface
     */
    protected MerchantResolverInterface $merchantResolver;

    /**
     * The signature service instance.
     *
     * @var SignatureService
     */
    protected SignatureService $signatureService;

    /**
     * Create a new multi merchant driver instance.
     *
     * @param MerchantResolverInterface $merchantResolver
     * @param SignatureService $signatureService
     */
    public function __construct(
        MerchantResolverInterface $merchantResolver,
        SignatureService $signatureService
    ) {
        $this->merchantResolver = $merchantResolver;
        $this->signatureService = $signatureService;
    }

    /**
     * Get merchant configuration using resolver.
     *
     * @param array $context
     * @return array
     *
     * @throws TelebirrException
     */
    public function getMerchantConfig(array $context = []): array
    {
        $merchant = $this->merchantResolver->resolve($context);

        if (!$merchant) {
            throw new TelebirrException(
                'Merchant not found for the given context',
                null,
                $context
            );
        }

        return [
            'id' => $merchant->id,
            'fabric_app_id' => config('telebirr.single_merchant.fabric_app_id'), // Shared fabric config
            'merchant_app_id' => $merchant->merchant_app_id,
            'merchant_code' => $merchant->merchant_code,
            'app_secret' => config('telebirr.single_merchant.app_secret'), // Shared app secret
            'name' => $merchant->name ?? null,
        ];
    }

    /**
     * Get fabric token for multi merchant.
     *
     * @param array $context
     * @return string|null
     */
    public function getFabricToken(array $context = []): ?string
    {
        return config('telebirr.single_merchant.fabric_app_id');
    }

    /**
     * Get API keys for multi merchant.
     *
     * @param array $context
     * @return array
     *
     * @throws TelebirrException
     */
    public function getApiKeys(array $context = []): array
    {
        $merchant = $this->merchantResolver->resolve($context);

        if (!$merchant) {
            throw new TelebirrException(
                'Merchant not found for the given context',
                null,
                $context
            );
        }

        return [
            'fabric_app_id' => config('telebirr.single_merchant.fabric_app_id'),
            'merchant_app_id' => $merchant->merchant_app_id,
            'merchant_code' => $merchant->merchant_code,
            'app_secret' => config('telebirr.single_merchant.app_secret'),
            'rsa_private_key' => $merchant->rsa_private_key ?? config('telebirr.single_merchant.rsa_private_key'),
            'rsa_public_key' => $merchant->rsa_public_key ?? config('telebirr.single_merchant.rsa_public_key') ?? $this->signatureService->extractPublicKeyFromPrivateKey($merchant->rsa_private_key ?? config('telebirr.single_merchant.rsa_private_key')),
            'merchant' => $merchant,
        ];
    }

    /**
     * Validate that merchant configuration is present.
     *
     * @param array $context
     * @return bool
     */
    public function validateConfiguration(array $context = []): bool
    {
        try {
            $keys = $this->getApiKeys($context);

            $requiredKeys = ['fabric_app_id', 'merchant_app_id', 'merchant_code', 'app_secret', 'rsa_private_key'];

            foreach ($requiredKeys as $key) {
                if (empty($keys[$key])) {
                    return false;
                }
            }

            return true;
        } catch (TelebirrException $e) {
            return false;
        }
    }
}
