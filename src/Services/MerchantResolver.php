<?php

namespace Telebirr\LaravelTelebirr\Services;

use Telebirr\LaravelTelebirr\Contracts\MerchantResolverInterface;
use Telebirr\LaravelTelebirr\Models\Merchant;
use Telebirr\LaravelTelebirr\Exceptions\TelebirrException;

/**
 * Default merchant resolver for multi-merchant mode.
 *
 * This implementation provides a basic way to resolve merchants from context.
 * Users should extend this class or implement MerchantResolverInterface
 * for custom merchant resolution logic.
 */
class MerchantResolver implements MerchantResolverInterface
{
    /**
     * Resolve a merchant based on context.
     *
     * This default implementation looks for merchant resolution keys in the context:
     * - merchant_id: Direct merchant ID
     * - branch_id: Resolves via branch relationship
     * - store_id: Resolves via store relationship
     *
     * @param array $context
     * @return Merchant|null
     *
     * @throws TelebirrException
     */
    public function resolve(array $context): ?Merchant
    {
        // Direct merchant ID lookup
        if (isset($context['merchant_id'])) {
            return Merchant::find($context['merchant_id']);
        }

        // Configurable key resolution
        $keyName = config('telebirr.merchant.key_name', 'merchant_id');
        if (isset($context[$keyName])) {
            return Merchant::find($context[$keyName]);
        }

        // Polymorphic owner resolution - generic approach
        if (isset($context['owner_type']) && isset($context['owner_id'])) {
            return Merchant::where('owner_type', $context['owner_type'])
                          ->where('owner_id', $context['owner_id'])
                          ->first();
        }

        // Specific owner type shortcuts for common use cases
        $ownerMappings = config('telebirr.merchant.owner_mappings', [
            'branch_id' => 'branch',
            'store_id' => 'store',
            'organization_id' => 'organization',
            'company_id' => 'company',
        ]);

        foreach ($ownerMappings as $contextKey => $ownerType) {
            if (isset($context[$contextKey])) {
                $result = Merchant::where('owner_type', $ownerType)
                                ->where('owner_id', $context[$contextKey])
                                ->first();

                if ($result) {
                    return $result;
                }

                // Fallback to legacy column names if enabled
                if (config('telebirr.merchant.legacy_branch_support', true)) {
                    $legacyColumn = str_replace('_id', '_id', $contextKey); // e.g., branch_id -> branch_id
                    $result = Merchant::where($legacyColumn, $context[$contextKey])->first();
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        // No resolution possible
        if (config('telebirr.mode') === 'multi') {
            throw new TelebirrException(
                "Unable to resolve merchant from context. Context: " . json_encode($context),
                null,
                $context
            );
        }

        return null; // Single merchant mode doesn't need resolution
    }
}
