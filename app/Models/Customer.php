<?php

namespace App\Models;

use App\Models\CustomerAddress;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Filament\Models\Contracts\HasName;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable implements HasAvatar, HasName
{

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'token',
        'avatar_url',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Scope to filter active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive customers
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to search customers by name, email, or phone
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter customers with phone numbers
     */
    public function scopeWithPhone($query)
    {
        return $query->whereNotNull('phone');
    }

    /**
     * Scope to filter customers without phone numbers
     */
    public function scopeWithoutPhone($query)
    {
        return $query->whereNull('phone');
    }

    /**
     * Scope to filter customers with avatars
     */
    public function scopeWithAvatar($query)
    {
        return $query->whereNotNull('avatar_url');
    }

    /**
     * Scope to filter customers without avatars
     */
    public function scopeWithoutAvatar($query)
    {
        return $query->whereNull('avatar_url');
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        $this->update(['last_login' => now()]);
        return $this;
    }

    /**
     * Check if customer has been active recently
     */
    public function isRecentlyActive($days = 30)
    {
        return $this->last_login && $this->last_login->isAfter(now()->subDays($days));
    }

    /**
     * Get customer's registration age in days
     */
    public function getRegistrationAgeAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get registration days ago as computed attribute
     */
    public function getRegistrationDaysAgoAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if customer has phone
     */
    public function getHasPhoneAttribute()
    {
        return !empty($this->phone);
    }

    /**
     * Check if customer has avatar
     */
    public function getHasAvatarAttribute()
    {
        return !empty($this->avatar_url);
    }

    /**
     * Check if customer has complete profile
     */
    public function hasCompleteProfile()
    {
        return !empty($this->name) && !empty($this->email) && !empty($this->phone);
    }

    /**
     * Generate a new API token for the customer
     */
    public function generateApiToken($tokenName = 'auth_token')
    {
        // Generate custom token
        $token = bin2hex(random_bytes(32));

        // Update token field
        $this->update(['token' => $token]);

        return $token;
    }

    /**
     * Activate the customer
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    /**
     * Deactivate the customer
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    /**
     * Toggle customer active status
     */
    public function toggleStatus()
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this;
    }

    /**
     * Get customer addresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }


    /**
     * Get customer tasks/bookings
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
