<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ServiceProvider extends Model
{
    protected $fillable = [
        'sp_user_id',
        'rating',
        'total_ratings',
        'acceptance_rate',
        'punctuality_score',
        'behaviour_score',
        'is_gold_level',
        'cancellation_score',
        'complaint_score',
        'rejection_frequency',
        'cooldown_until',
        'last_assigned_at',
        'tasks_completed',
        'tasks_cancelled',
        'tasks_rejected',
        'kyc_verified',
        'kyc_status',
        'kyc_rejection_reason',
        'is_blocked',
        'block_reason',
        'blocked_until',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'acceptance_rate' => 'decimal:2',
        'punctuality_score' => 'decimal:2',
        'behaviour_score' => 'decimal:2',
        'is_gold_level' => 'boolean',
        'cancellation_score' => 'decimal:2',
        'complaint_score' => 'decimal:2',
        'rejection_frequency' => 'integer',
        'cooldown_until' => 'datetime',
        'last_assigned_at' => 'datetime',
        'tasks_completed' => 'integer',
        'tasks_cancelled' => 'integer',
        'tasks_rejected' => 'integer',
        'kyc_verified' => 'boolean',
        'is_blocked' => 'boolean',
        'blocked_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function spUser(): BelongsTo
    {
        return $this->belongsTo(SPUser::class);
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(SpCapability::class);
    }

    public function cuisineCapabilities(): HasMany
    {
        return $this->hasMany(SpCuisineCapability::class);
    }

    public function dietaryCapabilities(): HasMany
    {
        return $this->hasMany(SpDietaryCapability::class);
    }

    public function addonCapabilities(): HasMany
    {
        return $this->hasMany(SpAddonCapability::class);
    }

    public function optionalCapabilities(): HasMany
    {
        return $this->hasMany(SpOptionalCapability::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function locationTracking(): HasMany
    {
        return $this->hasMany(SpLocationTracking::class);
    }

    public function taskBroadcasts(): HasMany
    {
        return $this->hasMany(TaskBroadcast::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('kyc_verified', true);
    }

    public function scopeKycApproved(Builder $query): Builder
    {
        return $query->where('kyc_status', 'approved');
    }

    public function scopeNotBlocked(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_blocked', false)
              ->orWhere('blocked_until', '<', now());
        });
    }

    public function scopeNotInCooldown(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('cooldown_until')
              ->orWhere('cooldown_until', '<', now());
        });
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->whereHas('spUser', function ($q) {
            $q->where('is_online', true);
        });
    }

    public function scopeGoldLevel(Builder $query): Builder
    {
        return $query->where('is_gold_level', true);
    }

    public function scopeRated(Builder $query): Builder
    {
        return $query->where('total_ratings', '>=', 5)->where('rating', '>=', 4.0);
    }

    public function scopeNewSp(Builder $query): Builder
    {
        return $query->where('total_ratings', '<', 5);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->whereHas('capabilities', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)->where('is_active', true);
        });
    }

    public function scopeBySubcategory(Builder $query, int $subcategoryId): Builder
    {
        return $query->whereHas('capabilities', function ($q) use ($subcategoryId) {
            $q->where('subcategory_id', $subcategoryId)->where('is_active', true);
        });
    }

    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, int $radiusKm): Builder
    {
        return $query->whereHas('spUser', function ($q) use ($latitude, $longitude, $radiusKm) {
            $q->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                [$latitude, $longitude, $latitude, $radiusKm]
            );
        });
    }

    public function scopeCanWorkNights(Builder $query): Builder
    {
        return $query->whereHas('spUser', function ($q) {
            $q->where('can_work_nights', true);
        });
    }

    public function scopeWithPaxCapacity(Builder $query, int $paxCount, int $subcategoryId): Builder
    {
        return $query->whereHas('capabilities', function ($q) use ($paxCount, $subcategoryId) {
            $q->where('subcategory_id', $subcategoryId)
              ->where('max_pax_capacity', '>=', $paxCount)
              ->where('is_active', true);
        });
    }

    // Helper Methods
    public function isAvailable(): bool
    {
        return $this->is_active 
            && $this->kyc_verified 
            && !$this->is_blocked 
            && !$this->isInCooldown() 
            && $this->spUser->is_online;
    }

    public function isInCooldown(): bool
    {
        return $this->cooldown_until && $this->cooldown_until->isFuture();
    }

    public function isBlocked(): bool
    {
        return $this->is_blocked && (!$this->blocked_until || $this->blocked_until->isFuture());
    }

    public function getQualityScore(): float
    {
        // Weighted quality score for allocation engine
        $weights = [
            'rating' => 0.3,
            'acceptance_rate' => 0.2,
            'punctuality_score' => 0.2,
            'behaviour_score' => 0.15,
            'cancellation_score' => 0.1,
            'complaint_score' => 0.05,
        ];

        $score = 0;
        $score += ($this->rating / 5) * $weights['rating'] * 100;
        $score += $this->acceptance_rate * $weights['acceptance_rate'];
        $score += $this->punctuality_score * $weights['punctuality_score'];
        $score += $this->behaviour_score * $weights['behaviour_score'];
        $score += (100 - $this->cancellation_score) * $weights['cancellation_score'];
        $score += (100 - $this->complaint_score) * $weights['complaint_score'];

        return round($score, 2);
    }

    public function getDistanceFrom(float $latitude, float $longitude): float
    {
        return $this->spUser->getDistanceFrom($latitude, $longitude);
    }

    public function canHandleSubcategory(int $subcategoryId): bool
    {
        return $this->capabilities()
            ->where('subcategory_id', $subcategoryId)
            ->where('is_active', true)
            ->exists();
    }

    public function canHandleCuisines(array $cuisineIds): bool
    {
        if (empty($cuisineIds)) {
            return true;
        }

        $availableCuisines = $this->cuisineCapabilities()
            ->where('is_active', true)
            ->pluck('chef_cuisine_id')
            ->toArray();

        return !empty(array_intersect($cuisineIds, $availableCuisines));
    }

    public function canHandleDietaryPreference(int $dietaryPreferenceId): bool
    {
        return $this->dietaryCapabilities()
            ->where('dietary_preference_id', $dietaryPreferenceId)
            ->where('is_active', true)
            ->exists();
    }

    public function canHandleAddonFlags(array $addonFlagIds): bool
    {
        if (empty($addonFlagIds)) {
            return true;
        }

        $availableFlags = $this->addonCapabilities()
            ->where('is_active', true)
            ->pluck('chef_addon_flag_id')
            ->toArray();

        return empty(array_diff($addonFlagIds, $availableFlags));
    }

    public function canHandleOptionalFlags(array $optionalFlagIds): bool
    {
        if (empty($optionalFlagIds)) {
            return true;
        }

        $availableFlags = $this->optionalCapabilities()
            ->where('is_active', true)
            ->pluck('optional_flag_id')
            ->toArray();

        return empty(array_diff($optionalFlagIds, $availableFlags));
    }

    public function updateRating(int $newRating): void
    {
        $totalRatings = $this->total_ratings;
        $currentRating = $this->rating;

        $newTotalRatings = $totalRatings + 1;
        $newAverageRating = (($currentRating * $totalRatings) + $newRating) / $newTotalRatings;

        $this->update([
            'rating' => round($newAverageRating, 2),
            'total_ratings' => $newTotalRatings,
        ]);
    }

    public function setCooldown(int $minutes): void
    {
        $this->update([
            'cooldown_until' => now()->addMinutes($minutes),
        ]);
    }

    public function blockTemporarily(string $reason, int $hours): void
    {
        $this->update([
            'is_blocked' => true,
            'block_reason' => $reason,
            'blocked_until' => now()->addHours($hours),
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'is_blocked' => false,
            'block_reason' => null,
            'blocked_until' => null,
        ]);
    }
}