<?php

namespace Telebirr\LaravelTelebirr\Drivers;

interface DriverInterface
{
    /**
     * Get the merchant configuration for the given context.
     *
     * @param array $context
     * @return array
     */
    public function getMerchantConfig(array $context = []): array;

    /**
     * Get the fabric token for the merchant.
     *
     * @param array $context
     * @return string|null
     */
    public function getFabricToken(array $context = []): ?string;

    /**
     * Get the API keys for the merchant.
     *
     * @param array $context
     * @return array
     */
    public function getApiKeys(array $context = []): array;
}
