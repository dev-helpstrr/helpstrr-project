<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPDriverDetail extends Model
{
    protected $table = 'sp_driver_details';

    protected $fillable = [
        'sp_user_id',
        'license_number',
        'license_front_image',
        'license_back_image',
        'license_expiry_date',
        'years_of_experience',
        'city_driving_experience',
        'highway_driving_experience',
        'night_driving_experience',
        'traffic_heavy_experience',
        'transmission_types',
        'vehicle_segments',
        'ready_for_outstation',
        'ready_for_oneway_outstation',
        'ready_for_roundtrip_outstation',
        'ready_for_airport_pickup',
        'ready_for_office_commute',
        'comfortable_long_hours',
        'comfortable_luggage_handling',
        'comfortable_waiting_time',
        'expected_hourly_rate',
        'expected_daily_rate',
        'outstation_per_km_rate',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'city_driving_experience' => 'boolean',
        'highway_driving_experience' => 'boolean',
        'night_driving_experience' => 'boolean',
        'traffic_heavy_experience' => 'boolean',
        'transmission_types' => 'array',
        'vehicle_segments' => 'array',
        'ready_for_outstation' => 'boolean',
        'ready_for_oneway_outstation' => 'boolean',
        'ready_for_roundtrip_outstation' => 'boolean',
        'ready_for_airport_pickup' => 'boolean',
        'ready_for_office_commute' => 'boolean',
        'comfortable_long_hours' => 'boolean',
        'comfortable_luggage_handling' => 'boolean',
        'comfortable_waiting_time' => 'boolean',
        'expected_hourly_rate' => 'decimal:2',
        'expected_daily_rate' => 'decimal:2',
        'outstation_per_km_rate' => 'decimal:2',
    ];

    public function spUser(): BelongsTo
    {
        return $this->belongsTo(SPUser::class, 'sp_user_id');
    }

    // Utility methods
    public function getExperienceTypesAttribute(): array
    {
        $types = [];
        if ($this->city_driving_experience) $types[] = 'City Driving';
        if ($this->highway_driving_experience) $types[] = 'Highway Driving';
        if ($this->night_driving_experience) $types[] = 'Night Driving';
        if ($this->traffic_heavy_experience) $types[] = 'Traffic Heavy Areas';
        
        return $types;
    }

    public function getServiceCapabilitiesAttribute(): array
    {
        $capabilities = [];
        if ($this->ready_for_outstation) $capabilities[] = 'Outstation Trips';
        if ($this->ready_for_oneway_outstation) $capabilities[] = 'One-way Outstation';
        if ($this->ready_for_roundtrip_outstation) $capabilities[] = 'Round-trip Outstation';
        if ($this->ready_for_airport_pickup) $capabilities[] = 'Airport Pickup/Drop';
        if ($this->ready_for_office_commute) $capabilities[] = 'Office Commute';
        
        return $capabilities;
    }

    public function getComfortLevelsAttribute(): array
    {
        $comfort = [];
        if ($this->comfortable_long_hours) $comfort[] = 'Long Hours';
        if ($this->comfortable_luggage_handling) $comfort[] = 'Luggage Handling';
        if ($this->comfortable_waiting_time) $comfort[] = 'Waiting Time';
        
        return $comfort;
    }
}