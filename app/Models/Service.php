<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'icon',
        'image',
        'base_price',
        'hourly_rate',
        'min_hours',
        'max_hours',
        'consultation_fee',
        'travel_allowance',
        'pax_required',
        'min_pax',
        'max_pax',
        'recurrence_allowed',
        'is_event_service',
        'is_takeaway',
        'requires_verification',
        'is_active',
        'sort_order',
        'requirements',
        'features',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'min_hours' => 'integer',
        'max_hours' => 'integer',
        'consultation_fee' => 'decimal:2',
        'travel_allowance' => 'decimal:2',
        'pax_required' => 'boolean',
        'min_pax' => 'integer',
        'max_pax' => 'integer',
        'recurrence_allowed' => 'boolean',
        'is_event_service' => 'boolean',
        'is_takeaway' => 'boolean',
        'requires_verification' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'requirements' => 'array',
        'features' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
        
        static::updating(function ($service) {
            if ($service->isDirty('name') && empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    // Many-to-Many Relationships
    public function subcategories(): BelongsToMany
    {
        return $this->belongsToMany(NewSubcategory::class, 'subcategory_service', 'service_id', 'subcategory_id')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderByPivot('sort_order')
                    ->orderByPivot('is_primary', 'desc');
    }

    public function primarySubcategories(): BelongsToMany
    {
        return $this->subcategories()->wherePivot('is_primary', true);
    }

    public function secondarySubcategories(): BelongsToMany
    {
        return $this->subcategories()->wherePivot('is_primary', false);
    }

    // Through relationships to get categories
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(NewCategory::class, 'subcategory_service', 'service_id', 'subcategory_id')
                    ->join('category_subcategory', 'new_subcategories.id', '=', 'category_subcategory.subcategory_id')
                    ->join('new_categories', 'category_subcategory.category_id', '=', 'new_categories.id')
                    ->select('new_categories.*')
                    ->distinct();
    }

    // Legacy relationships (for backward compatibility)
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'service_id');
    }

    public function spCapabilities(): HasMany
    {
        return $this->hasMany(SpCapability::class, 'service_id');
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

    public function scopeBySubcategory(Builder $query, int $subcategoryId): Builder
    {
        return $query->whereHas('subcategories', function ($q) use ($subcategoryId) {
            $q->where('new_subcategories.id', $subcategoryId);
        });
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('new_categories.id', $categoryId);
        });
    }

    public function scopeRequiresPax(Builder $query): Builder
    {
        return $query->where('pax_required', true);
    }

    public function scopeAllowsRecurrence(Builder $query): Builder
    {
        return $query->where('recurrence_allowed', true);
    }

    public function scopeEventServices(Builder $query): Builder
    {
        return $query->where('is_event_service', true);
    }

    public function scopeTakeawayServices(Builder $query): Builder
    {
        return $query->where('is_takeaway', true);
    }

    public function scopeRequiresVerification(Builder $query): Builder
    {
        return $query->where('requires_verification', true);
    }

    public function scopePriceRange(Builder $query, ?float $minPrice = null, ?float $maxPrice = null): Builder
    {
        if ($minPrice !== null) {
            $query->where(function ($q) use ($minPrice) {
                $q->where('base_price', '>=', $minPrice)
                  ->orWhere('hourly_rate', '>=', $minPrice);
            });
        }
        
        if ($maxPrice !== null) {
            $query->where(function ($q) use ($maxPrice) {
                $q->where('base_price', '<=', $maxPrice)
                  ->orWhere('hourly_rate', '<=', $maxPrice);
            });
        }
        
        return $query;
    }

    // Helper Methods
    public function getActiveSubcategoriesCount(): int
    {
        return $this->subcategories()->where('new_subcategories.is_active', true)->count();
    }

    public function getActiveCategoriesCount(): int
    {
        return $this->categories()->where('new_categories.is_active', true)->count();
    }

    public function hasSubcategories(): bool
    {
        return $this->subcategories()->exists();
    }

    public function getPrimarySubcategory(): ?NewSubcategory
    {
        return $this->primarySubcategories()->first();
    }

    public function attachSubcategory(NewSubcategory $subcategory, bool $isPrimary = false, int $sortOrder = 0): void
    {
        $this->subcategories()->attach($subcategory->id, [
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function detachSubcategory(NewSubcategory $subcategory): void
    {
        $this->subcategories()->detach($subcategory->id);
    }

    public function calculateBillableHours(int $requestedHours): int
    {
        return max($requestedHours, $this->min_hours);
    }

    public function calculateBaseAmount(int $billableHours): float
    {
        if ($this->base_price) {
            return $this->base_price;
        }
        
        return $this->hourly_rate * $billableHours;
    }

    public function calculateTotalAmount(int $billableHours, int $pax = 1): float
    {
        $baseAmount = $this->calculateBaseAmount($billableHours);
        $consultationFee = $this->consultation_fee;
        $travelAllowance = $this->travel_allowance;
        
        if ($this->pax_required && $pax > 1) {
            $baseAmount *= $pax;
        }
        
        return $baseAmount + $consultationFee + $travelAllowance;
    }

    public function hasConsultationFee(): bool
    {
        return $this->consultation_fee > 0;
    }

    public function hasTravelAllowance(): bool
    {
        return $this->travel_allowance > 0;
    }

    public function isWithinPaxLimits(int $pax): bool
    {
        if (!$this->pax_required) {
            return true;
        }
        
        $withinMin = $pax >= $this->min_pax;
        $withinMax = $this->max_pax ? $pax <= $this->max_pax : true;
        
        return $withinMin && $withinMax;
    }

    public function isWithinHourLimits(int $hours): bool
    {
        $withinMin = $hours >= $this->min_hours;
        $withinMax = $this->max_hours ? $hours <= $this->max_hours : true;
        
        return $withinMin && $withinMax;
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $primarySubcategory = $this->getPrimarySubcategory();
        if ($primarySubcategory) {
            $primaryCategory = $primarySubcategory->getPrimaryCategory();
            if ($primaryCategory) {
                return $primaryCategory->name . ' - ' . $primarySubcategory->name . ' - ' . $this->name;
            }
            return $primarySubcategory->name . ' - ' . $this->name;
        }
        return $this->name;
    }

    public function getFullPathAttribute(): string
    {
        $paths = [];
        foreach ($this->subcategories as $subcategory) {
            foreach ($subcategory->categories as $category) {
                $paths[] = $category->name . ' > ' . $subcategory->name . ' > ' . $this->name;
            }
        }
        return implode(' | ', array_unique($paths));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function getPriceDisplayAttribute(): string
    {
        if ($this->base_price) {
            return '₹' . number_format($this->base_price, 2);
        }
        
        if ($this->hourly_rate) {
            return '₹' . number_format($this->hourly_rate, 2) . '/hr';
        }
        
        return 'Price on request';
    }
}