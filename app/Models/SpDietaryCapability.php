<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpDietaryCapability extends Model
{
    protected $fillable = [
        'service_provider_id',
        'dietary_preference_id',
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

    public function dietaryPreference(): BelongsTo
    {
        return $this->belongsTo(DietaryPreference::class);
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

    public function scopeByDietaryPreference(Builder $query, int $dietaryPreferenceId): Builder
    {
        return $query->where('dietary_preference_id', $dietaryPreferenceId);
    }
}