<?php

namespace Telebirr\LaravelTelebirr\Contracts;

interface MerchantResolverInterface
{
    /**
     * Resolve the merchant configuration for a given context.
     *
     * @param array $context Context data (e.g., ['branch_id' => 1, 'store_id' => 2])
     * @return \Telebirr\LaravelTelebirr\Models\Merchant|null
     */
    public function resolve(array $context): ?\Telebirr\LaravelTelebirr\Models\Merchant;
}
