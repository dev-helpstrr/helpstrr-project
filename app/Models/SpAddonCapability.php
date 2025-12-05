<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpAddonCapability extends Model
{
    protected $fillable = [
        'service_provider_id',
        'chef_addon_flag_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function chefAddonFlag(): BelongsTo
    {
        return $this->belongsTo(ChefAddonFlag::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByServiceProvider(Builder $query, int $serviceProviderId): Builder
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    public function scopeByAddonFlag(Builder $query, int $addonFlagId): Builder
    {
        return $query->where('chef_addon_flag_id', $addonFlagId);
    }
}