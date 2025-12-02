<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPHouseHelpDetail extends Model
{
    protected $table = 'sp_house_help_details';

    protected $fillable = [
        'sp_user_id',
        'can_clean_utensils',
        'can_sweep_mop',
        'can_dust',
        'can_wash_clothes',
        'can_iron_clothes',
        'can_clean_bathroom',
        'can_clean_kitchen',
        'can_deep_clean',
        'comfortable_with_pets',
        'max_hours_per_task',
        'can_assist_elderly',
        'can_handle_babies',
        'can_cook_basic_meals',
        'can_grocery_shopping',
        'can_gardening',
        'can_car_cleaning',
        'preferred_work_timings',
        'available_weekends',
        'available_festivals',
        'min_hours_per_day',
        'max_hours_per_day',
        'hourly_rate',
        'daily_rate',
        'monthly_rate',
        'has_own_cleaning_supplies',
        'has_own_equipment',
        'available_equipment',
    ];

    protected $casts = [
        'can_clean_utensils' => 'boolean',
        'can_sweep_mop' => 'boolean',
        'can_dust' => 'boolean',
        'can_wash_clothes' => 'boolean',
        'can_iron_clothes' => 'boolean',
        'can_clean_bathroom' => 'boolean',
        'can_clean_kitchen' => 'boolean',
        'can_deep_clean' => 'boolean',
        'comfortable_with_pets' => 'boolean',
        'can_assist_elderly' => 'boolean',
        'can_handle_babies' => 'boolean',
        'can_cook_basic_meals' => 'boolean',
        'can_grocery_shopping' => 'boolean',
        'can_gardening' => 'boolean',
        'can_car_cleaning' => 'boolean',
        'preferred_work_timings' => 'array',
        'available_weekends' => 'boolean',
        'available_festivals' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'has_own_cleaning_supplies' => 'boolean',
        'has_own_equipment' => 'boolean',
        'available_equipment' => 'array',
    ];

    public function spUser(): BelongsTo
    {
        return $this->belongsTo(SPUser::class, 'sp_user_id');
    }

    // Utility methods
    public function getSkillsAttribute(): array
    {
        $skills = [];
        if ($this->can_clean_utensils) $skills[] = 'Utensils Cleaning';
        if ($this->can_sweep_mop) $skills[] = 'Sweeping & Mopping';
        if ($this->can_dust) $skills[] = 'Dusting';
        if ($this->can_wash_clothes) $skills[] = 'Clothes Washing';
        if ($this->can_iron_clothes) $skills[] = 'Clothes Ironing';
        if ($this->can_clean_bathroom) $skills[] = 'Bathroom Cleaning';
        if ($this->can_clean_kitchen) $skills[] = 'Kitchen Cleaning';
        if ($this->can_deep_clean) $skills[] = 'Deep Cleaning';
        
        return $skills;
    }

    public function getAdditionalCapabilitiesAttribute(): array
    {
        $capabilities = [];
        if ($this->comfortable_with_pets) $capabilities[] = 'Comfortable with Pets';
        if ($this->can_assist_elderly) $capabilities[] = 'Elderly Assistance';
        if ($this->can_handle_babies) $capabilities[] = 'Baby Handling';
        if ($this->can_cook_basic_meals) $capabilities[] = 'Basic Cooking';
        if ($this->can_grocery_shopping) $capabilities[] = 'Grocery Shopping';
        if ($this->can_gardening) $capabilities[] = 'Gardening';
        if ($this->can_car_cleaning) $capabilities[] = 'Car Cleaning';
        
        return $capabilities;
    }

    public function getWorkPreferencesAttribute(): array
    {
        $preferences = [];
        if ($this->available_weekends) $preferences[] = 'Available Weekends';
        if ($this->available_festivals) $preferences[] = 'Available Festivals';
        if ($this->has_own_cleaning_supplies) $preferences[] = 'Has Own Supplies';
        if ($this->has_own_equipment) $preferences[] = 'Has Own Equipment';
        
        return $preferences;
    }

    public function getWorkingHoursRangeAttribute(): string
    {
        if ($this->min_hours_per_day && $this->max_hours_per_day) {
            return "{$this->min_hours_per_day}-{$this->max_hours_per_day} hours/day";
        } elseif ($this->max_hours_per_day) {
            return "Up to {$this->max_hours_per_day} hours/day";
        } elseif ($this->min_hours_per_day) {
            return "Minimum {$this->min_hours_per_day} hours/day";
        }
        
        return 'Flexible hours';
    }
}