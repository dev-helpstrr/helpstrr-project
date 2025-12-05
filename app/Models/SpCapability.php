<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpCapability extends Model
{
    protected $fillable = [
        'service_provider_id',
        'category_id',
        'subcategory_id',
        'service_id',
        'max_pax_capacity',
        'night_shift_available',
        'max_travel_distance_km',
        'is_active',
    ];

    protected $casts = [
        'max_pax_capacity' => 'integer',
        'night_shift_available' => 'boolean',
        'max_travel_distance_km' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(NewSubcategory::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySubcategory(Builder $query, int $subcategoryId): Builder
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    public function scopeByService(Builder $query, int $serviceId): Builder
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeNightShiftAvailable(Builder $query): Builder
    {
        return $query->where('night_shift_available', true);
    }

    public function scopeWithPaxCapacity(Builder $query, int $minCapacity): Builder
    {
        return $query->where('max_pax_capacity', '>=', $minCapacity);
    }

    public function scopeWithinTravelDistance(Builder $query, int $maxDistance): Builder
    {
        return $query->where('max_travel_distance_km', '>=', $maxDistance);
    }

    // Helper Methods
    public function canHandlePax(int $paxCount): bool
    {
        return $this->max_pax_capacity >= $paxCount;
    }

    public function canWorkNights(): bool
    {
        return $this->night_shift_available;
    }

    public function canTravelDistance(int $distanceKm): bool
    {
        return $this->max_travel_distance_km >= $distanceKm;
    }
}