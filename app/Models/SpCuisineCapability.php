<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpCuisineCapability extends Model
{
    protected $fillable = [
        'service_provider_id',
        'chef_cuisine_id',
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

    public function chefCuisine(): BelongsTo
    {
        return $this->belongsTo(ChefCuisine::class);
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

    public function scopeByCuisine(Builder $query, int $cuisineId): Builder
    {
        return $query->where('chef_cuisine_id', $cuisineId);
    }
}