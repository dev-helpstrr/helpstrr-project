<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SPUser extends Authenticatable implements HasAvatar, HasName
{
    use HasApiTokens;
    protected $table = 's_p_users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile1_number',
        'whatsapp',
        'dob',
        'age',
        'gender',
        'alternate_mobile',
        'languages_known',
        'mobile_verified',
        'alternate_mobile_verified',
        'profile_status',
        'avatar_url',
        'intrested_role',
        'service_categories',
        'prior_experience',
        'password',
        'confirm_password_hash',
        'address',
        'city',
        'country',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'coverage_radius',
        'has_two_wheeler',
        'experience_years',
        'bio',
        'profile_picture',
        'id_proof',
        'background_check_status',
        'avg_rating',
        'total_ratings',
        'is_verified',
        'is_active',
        'last_login',
        'daily_availability',
        'weekly_off_days',
        'is_online',
        'max_travel_distance',
        'employer_references',
        'work_portfolio',
        'certifications',
        'preferred_working_areas',
        'preferred_task_types',
        'max_daily_working_hours',
        'special_conditions',
        'expected_hourly_rate',
        'expected_daily_rate',
        'can_work_weekends',
        'can_work_nights',
        'additional_notes',

        'service_category_id',];

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'confirm_password_hash',
        'token',
        'login_otp',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'mobile_verified' => 'boolean',
        'alternate_mobile_verified' => 'boolean',
        'is_online' => 'boolean',
        'can_work_weekends' => 'boolean',
        'can_work_nights' => 'boolean',
        'last_login' => 'datetime',
        'avg_rating' => 'decimal:2',
        'expected_hourly_rate' => 'decimal:2',
        'expected_daily_rate' => 'decimal:2',
        'languages_known' => 'array',
        'service_categories' => 'array',
        'daily_availability' => 'array',
        'weekly_off_days' => 'array',
        'employer_references' => 'array',
        'work_portfolio' => 'array',
        'certifications' => 'array',
        'preferred_working_areas' => 'array',
        'preferred_task_types' => 'array',
    ];

    // Relationships
    public function driverDetail(): HasOne
    {
        return $this->hasOne(SPDriverDetail::class, 'sp_user_id');
    }

    public function chefDetail(): HasOne
    {
        return $this->hasOne(SPChefDetail::class, 'sp_user_id');
    }

    public function houseHelpDetail(): HasOne
    {
        return $this->hasOne(SPHouseHelpDetail::class, 'sp_user_id');
    }

    public function availabilitySchedules(): HasMany
    {
        return $this->hasMany(SPAvailabilitySchedule::class, 'sp_user_id');
    }

    // Filament Interface Methods
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->first_name
            ? trim($this->first_name . ' ' . $this->last_name)
            : ($this->email ?? 'Unknown');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->whereJsonContains('service_categories', $category);
    }

    public function scopeByLocation(Builder $query, string $city = null, string $state = null): Builder
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        if ($state) {
            $query->where('state', 'like', "%{$state}%");
        }
        return $query;
    }

    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, int $radiusKm = 10): Builder
    {
        return $query->whereRaw(
            "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
            [$latitude, $longitude, $latitude, $radiusKm]
        );
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('mobile1_number', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    // Utility Methods
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getServiceCategoriesListAttribute(): array
    {
        $categoryMap = [
            'house_help' => 'House Help',
            'driver' => 'Driver',
            'chef' => 'Chef'
        ];

        return array_map(function($category) use ($categoryMap) {
            return $categoryMap[$category] ?? ucfirst($category);
        }, $this->service_categories ?? []);
    }

    public function getLanguagesListAttribute(): array
    {
        return $this->languages_known ?? [];
    }

    public function getRegistrationDaysAgoAttribute(): float
    {
        return $this->created_at ? $this->created_at->diffInDays(now()) : 0;
    }

    public function hasCategory(string $category): bool
    {
        return in_array($category, $this->service_categories ?? []);
    }

    public function isDriver(): bool
    {
        return $this->hasCategory('driver');
    }

    public function isChef(): bool
    {
        return $this->hasCategory('chef');
    }

    public function isHouseHelp(): bool
    {
        return $this->hasCategory('house_help');
    }

    public function getStatusBadgeAttribute(): array
    {
        if (!$this->is_active) {
            return ['text' => 'Inactive', 'color' => 'danger'];
        }

        if ($this->is_verified) {
            return ['text' => 'Verified', 'color' => 'success'];
        }

        return ['text' => 'Pending', 'color' => 'warning'];
    }

    public function getBackgroundCheckStatusTextAttribute(): string
    {
        return match($this->background_check_status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'pending' => 'Pending',
            default => 'Not Started'
        };
    }

    public function getAverageRatingStarsAttribute(): string
    {
        $rating = $this->avg_rating ?? 0;
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return str_repeat('★', $fullStars) .
               str_repeat('☆', $halfStar) .
               str_repeat('☆', $emptyStars);
    }

    public function canWorkToday(): bool
    {
        $today = now()->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        return !in_array($today, $this->weekly_off_days ?? []);
    }

    public function getTodayAvailability(): ?SPAvailabilitySchedule
    {
        $today = now()->dayOfWeek;
        return $this->availabilitySchedules()->forDay($today)->first();
    }

    public function isAvailableNow(): bool
    {
        if (!$this->is_online || !$this->is_active) {
            return false;
        }

        $todaySchedule = $this->getTodayAvailability();
        if (!$todaySchedule || !$todaySchedule->is_available) {
            return false;
        }

        $now = now()->format('H:i');
        $startTime = $todaySchedule->start_time?->format('H:i');
        $endTime = $todaySchedule->end_time?->format('H:i');

        if (!$startTime || !$endTime) {
            return true; // Available all day if no specific times set
        }

        return $now >= $startTime && $now <= $endTime;
    }

    public function getDistanceFrom(float $latitude, float $longitude): float
    {
        if (!$this->latitude || !$this->longitude) {
            return 0;
        }

        $earthRadius = 6371; // km

        $latDelta = deg2rad($latitude - $this->latitude);
        $lonDelta = deg2rad($longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }


    public function category()
    {
        return $this->belongsTo(\App\Models\ServiceCategory::class, 'service_category_id');
    }

}
