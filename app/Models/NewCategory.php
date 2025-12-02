<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewCategory extends Model
{
    protected $table = 'new_categories';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Many-to-Many Relationships
    public function subcategories(): BelongsToMany
    {
        return $this->belongsToMany(NewSubcategory::class, 'category_subcategory', 'category_id', 'subcategory_id')
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

    // Through relationships to get services
    public function allServices()
    {
        $serviceIds = collect();
        
        foreach ($this->subcategories as $subcategory) {
            $serviceIds = $serviceIds->merge($subcategory->services->pluck('id'));
        }
        
        return Service::whereIn('id', $serviceIds->unique())->get();
    }

    public function getServicesCount(): int
    {
        return $this->subcategories()
            ->withCount('services')
            ->get()
            ->sum('services_count');
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

    public function scopeWithActiveSubcategories(Builder $query): Builder
    {
        return $query->whereHas('subcategories', function ($q) {
            $q->where('new_subcategories.is_active', true);
        });
    }

    // Helper Methods
    public function getActiveSubcategoriesCount(): int
    {
        return $this->subcategories()->where('new_subcategories.is_active', true)->count();
    }



    public function hasSubcategories(): bool
    {
        return $this->subcategories()->exists();
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

    public function syncSubcategories(array $subcategoryIds, bool $detaching = true): array
    {
        $syncData = [];
        foreach ($subcategoryIds as $id => $data) {
            if (is_numeric($id)) {
                $syncData[$data] = [
                    'is_primary' => false,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $syncData[$id] = array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        return $this->subcategories()->sync($syncData, $detaching);
    }

    // Accessors
    public function getFullPathAttribute(): string
    {
        return $this->name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}