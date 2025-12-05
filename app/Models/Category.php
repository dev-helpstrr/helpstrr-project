<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'night_multiplier',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'night_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function spCapabilities(): HasMany
    {
        return $this->hasMany(SpCapability::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors & Mutators
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = $value ?: \Str::slug($this->name);
    }

    // Helper Methods
    public function getActiveSubcategoriesCount(): int
    {
        return $this->subcategories()->active()->count();
    }

    public function hasNightSurcharge(): bool
    {
        return $this->night_multiplier > 1.00;
    }

    public function getNightSurchargePercentage(): float
    {
        return ($this->night_multiplier - 1) * 100;
    }
}