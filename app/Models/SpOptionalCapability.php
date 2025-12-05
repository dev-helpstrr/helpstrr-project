<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpOptionalCapability extends Model
{
    protected $fillable = [
        'service_provider_id',
        'optional_flag_id',
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

    public function optionalFlag(): BelongsTo
    {
        return $this->belongsTo(OptionalFlag::class);
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

    public function scopeByOptionalFlag(Builder $query, int $optionalFlagId): Builder
    {
        return $query->where('optional_flag_id', $optionalFlagId);
    }

    public function scopeHardFilters(Builder $query): Builder
    {
        return $query->whereHas('optionalFlag', function ($q) {
            $q->where('is_hard_filter', true);
        });
    }

    public function scopeSoftFilters(Builder $query): Builder
    {
        return $query->whereHas('optionalFlag', function ($q) {
            $q->where('is_hard_filter', false);
        });
    }
}