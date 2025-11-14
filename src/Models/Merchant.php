<?php

namespace Telebirr\LaravelTelebirr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Telebirr Merchant Model
 *
 * Represents a merchant configuration for multi-merchant mode.
 * Each merchant can have their own API keys and settings.
 */
class Merchant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telebirr_merchants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'merchant_app_id',
        'merchant_code',
        'rsa_private_key',
        'rsa_public_key',
        'owner_id',
        'owner_type',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'rsa_private_key',
        'rsa_public_key',
    ];

    /**
     * Get the parent owner model (branch, store, organization, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }

    /**
     * Scope a query to only include active merchants.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find merchants by owner type and ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ownerType
     * @param int $ownerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOwner($query, string $ownerType, int $ownerId)
    {
        return $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
    }

    /**
     * Scope a query to find merchants by branch (legacy support).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBranch($query, $branchId)
    {
        if (config('telebirr.merchant.legacy_branch_support', true)) {
            return $query->where('owner_type', 'branch')->where('owner_id', $branchId)
                        ->orWhere('branch_id', $branchId); // backward compatibility
        }

        return $query->where('owner_type', 'branch')->where('owner_id', $branchId);
    }

    /**
     * Scope a query to find merchants by store (legacy support).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $storeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStore($query, $storeId)
    {
        if (config('telebirr.merchant.legacy_branch_support', true)) {
            return $query->where('owner_type', 'store')->where('owner_id', $storeId)
                        ->orWhere('store_id', $storeId); // backward compatibility
        }

        return $query->where('owner_type', 'store')->where('owner_id', $storeId);
    }

    /**
     * Check if the merchant is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchant_app_id) &&
               !empty($this->merchant_code) &&
               !empty($this->rsa_private_key);
    }

    /**
     * Get the merchant's settings.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting($key = null, $default = null)
    {
        if ($key === null) {
            return $this->settings ?? [];
        }

        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a merchant setting.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSetting($key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }
}
