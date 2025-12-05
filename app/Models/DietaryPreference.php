<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietaryPreference extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function spCapabilities(): HasMany
    {
        return $this->hasMany(SpDietaryCapability::class);
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
        $this->attributes['slug'] = $value ?: Str::slug($this->name);
    }

    // Helper Methods
    public function getServiceProvidersCount(): int
    {
        return $this->spCapabilities()->whereHas('serviceProvider', function ($q) {
            $q->active()->verified();
        })->count();
    }
}
