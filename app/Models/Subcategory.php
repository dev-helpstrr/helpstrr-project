<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Subcategory extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'hourly_rate',
        'min_hours',
        'consultation_fee',
        'pax_required',
        'recurrence_allowed',
        'is_event_category',
        'is_takeaway',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'min_hours' => 'integer',
        'consultation_fee' => 'decimal:2',
        'pax_required' => 'boolean',
        'recurrence_allowed' => 'boolean',
        'is_event_category' => 'boolean',
        'is_takeaway' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class);
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

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeRequiresPax(Builder $query): Builder
    {
        return $query->where('pax_required', true);
    }

    public function scopeAllowsRecurrence(Builder $query): Builder
    {
        return $query->where('recurrence_allowed', true);
    }

    public function scopeEventCategories(Builder $query): Builder
    {
        return $query->where('is_event_category', true);
    }

    public function scopeTakeawayCategories(Builder $query): Builder
    {
        return $query->where('is_takeaway', true);
    }

    // Accessors & Mutators
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = $value ?: \Str::slug($this->name);
    }

    // Helper Methods
    public function calculateBillableHours(int $requestedHours): int
    {
        return max($requestedHours, $this->min_hours);
    }

    public function calculateBaseAmount(int $billableHours): float
    {
        return $this->hourly_rate * $billableHours;
    }

    public function hasConsultationFee(): bool
    {
        return $this->consultation_fee > 0;
    }

    public function getFullNameAttribute(): string
    {
        return $this->category->name . ' - ' . $this->name;
    }

    public function isChefCategory(): bool
    {
        return $this->category->slug === 'chef';
    }

    public function isDriverCategory(): bool
    {
        return $this->category->slug === 'driver';
    }

    public function isHouseHelpCategory(): bool
    {
        return $this->category->slug === 'house-help';
    }
}