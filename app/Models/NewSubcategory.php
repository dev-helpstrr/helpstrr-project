<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewSubcategory extends Model
{
    protected $table = 'new_subcategories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subcategory) {
            if (empty($subcategory->slug)) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });

        static::updating(function ($subcategory) {
            if ($subcategory->isDirty('name') && empty($subcategory->slug)) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });
    }

    // Many-to-Many Relationships
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(NewCategory::class, 'category_subcategory', 'subcategory_id', 'category_id')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderByPivot('sort_order')
                    ->orderByPivot('is_primary', 'desc');
    }

    public function primaryCategories(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', true);
    }

    public function secondaryCategories(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', false);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'subcategory_service', 'subcategory_id', 'service_id')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderByPivot('sort_order')
                    ->orderByPivot('is_primary', 'desc');
    }

    public function primaryServices(): BelongsToMany
    {
        return $this->services()->wherePivot('is_primary', true);
    }

    public function secondaryServices(): BelongsToMany
    {
        return $this->services()->wherePivot('is_primary', false);
    }

    // Legacy relationships (for backward compatibility)
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'subcategory_id');
    }

    public function spCapabilities(): HasMany
    {
        return $this->hasMany(SpCapability::class, 'subcategory_id');
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

    public function scopeEventCategories(Builder $query): Builder
    {
        return $query->where('is_event_category', true);
    }

    public function scopeTakeawayCategories(Builder $query): Builder
    {
        return $query->where('is_takeaway', true);
    }

    public function scopeWithActiveServices(Builder $query): Builder
    {
        return $query->whereHas('services', function ($q) {
            $q->where('services.is_active', true);
        });
    }

    // Helper Methods
    public function getActiveCategoriesCount(): int
    {
        return $this->categories()->where('new_categories.is_active', true)->count();
    }

    public function getActiveServicesCount(): int
    {
        return $this->services()->where('services.is_active', true)->count();
    }

    public function hasCategories(): bool
    {
        return $this->categories()->exists();
    }

    public function hasServices(): bool
    {
        return $this->services()->exists();
    }

    public function getPrimaryCategory(): ?NewCategory
    {
        return $this->primaryCategories()->first();
    }


    public function attachCategory(NewCategory $category, bool $isPrimary = false, int $sortOrder = 0): void
    {
        $this->categories()->attach($category->id, [
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function detachCategory(NewCategory $category): void
    {
        $this->categories()->detach($category->id);
    }

    public function attachService(Service $service, bool $isPrimary = false, int $sortOrder = 0): void
    {
        $this->services()->attach($service->id, [
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function detachService(Service $service): void
    {
        $this->services()->detach($service->id);
    }

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

    // Accessors
    public function getFullNameAttribute(): string
    {
        $primaryCategory = $this->getPrimaryCategory();
        return $primaryCategory ? $primaryCategory->name . ' - ' . $this->name : $this->name;
    }

    public function getFullPathAttribute(): string
    {
        $categories = $this->categories()->pluck('name')->toArray();
        return implode(' > ', $categories) . ' > ' . $this->name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }


}
