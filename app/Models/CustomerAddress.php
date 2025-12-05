<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CustomerAddress extends Model
{

    protected $table = 'customer_addresses';


    protected $fillable = [
        'customer_id',
        'label',
        'type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'landmark',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeByState(Builder $query, string $state): Builder
    {
        return $query->where('state', 'like', "%{$state}%");
    }

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    // Helper Methods
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->landmark,
            $this->city,
            $this->state,
            $this->pincode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->city,
            $this->pincode,
        ]);

        return implode(', ', $parts);
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getDistanceFrom(float $latitude, float $longitude): float
    {
        if (!$this->hasCoordinates()) {
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

    public function makeDefault(): void
    {
        // Remove default from other addresses
        $this->customer->addresses()->where('id', '!=', $this->id)->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    public function getLabelDisplayAttribute(): string
    {
        return $this->label ?: 'Address ' . $this->id;
    }
}
