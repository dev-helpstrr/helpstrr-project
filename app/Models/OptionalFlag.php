<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class OptionalFlag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_hard_filter',
        'price_modifier',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_hard_filter' => 'boolean',
        'price_modifier' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function spCapabilities(): HasMany
    {
        return $this->hasMany(SpOptionalCapability::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_optional_flag_link');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHardFilters(Builder $query): Builder
    {
        return $query->where('is_hard_filter', true);
    }

    public function scopeSoftFilters(Builder $query): Builder
    {
        return $query->where('is_hard_filter', false);
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
    public function getServiceProvidersCount(): int
    {
        return $this->spCapabilities()->whereHas('serviceProvider', function ($q) {
            $q->active()->verified();
        })->count();
    }

    public function hasPriceModifier(): bool
    {
        return $this->price_modifier > 0;
    }

    public function calculatePriceAdjustment(float $baseAmount): float
    {
        if (!$this->hasPriceModifier()) {
            return 0;
        }

        return ($baseAmount * $this->price_modifier) / 100;
    }
}