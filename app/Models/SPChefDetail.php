<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPChefDetail extends Model
{
    protected $table = 'sp_chef_details';

    protected $fillable = [
        'sp_user_id',
        'years_of_cooking_experience',
        'chef_type',
        'cuisine_specialties',
        'pax_capacity',
        'can_cook_breakfast',
        'can_cook_lunch',
        'can_cook_dinner',
        'can_cook_full_day',
        'can_cook_special_occasions',
        'dietary_preference',
        'can_bring_utensils',
        'can_bring_raw_materials',
        'hygiene_certification',
        'price_per_meal',
        'price_per_day',
        'price_per_event',
        'food_portfolio_images',
        'speciality_description',
    ];

    protected $casts = [
        'cuisine_specialties' => 'array',
        'pax_capacity' => 'array',
        'can_cook_breakfast' => 'boolean',
        'can_cook_lunch' => 'boolean',
        'can_cook_dinner' => 'boolean',
        'can_cook_full_day' => 'boolean',
        'can_cook_special_occasions' => 'boolean',
        'can_bring_utensils' => 'boolean',
        'can_bring_raw_materials' => 'boolean',
        'price_per_meal' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'price_per_event' => 'decimal:2',
        'food_portfolio_images' => 'array',
    ];

    public function spUser(): BelongsTo
    {
        return $this->belongsTo(SPUser::class, 'sp_user_id');
    }

    // Utility methods
    public function getMealTypesAttribute(): array
    {
        $types = [];
        if ($this->can_cook_breakfast) $types[] = 'Breakfast';
        if ($this->can_cook_lunch) $types[] = 'Lunch';
        if ($this->can_cook_dinner) $types[] = 'Dinner';
        if ($this->can_cook_full_day) $types[] = 'Full Day';
        if ($this->can_cook_special_occasions) $types[] = 'Special Occasions';
        
        return $types;
    }

    public function getCuisineSpecialtiesListAttribute(): array
    {
        $cuisineMap = [
            'bengali' => 'Bengali',
            'north_indian' => 'North Indian',
            'south_indian' => 'South Indian',
            'chinese' => 'Chinese',
            'continental' => 'Continental',
            'mughlai' => 'Mughlai',
            'snacks' => 'Snacks/Party Starters',
            'biryani' => 'Biryani Specialist'
        ];

        return array_map(function($cuisine) use ($cuisineMap) {
            return $cuisineMap[$cuisine] ?? ucfirst($cuisine);
        }, $this->cuisine_specialties ?? []);
    }

    public function getPaxCapacityListAttribute(): array
    {
        $capacityMap = [
            '1-4' => '1-4 people',
            '5-10' => '5-10 people',
            '10-25' => '10-25 people',
            '25-50' => '25-50 people',
            '50+' => '50+ people (Events)'
        ];

        return array_map(function($capacity) use ($capacityMap) {
            return $capacityMap[$capacity] ?? $capacity;
        }, $this->pax_capacity ?? []);
    }

    public function getDietaryPreferenceTextAttribute(): string
    {
        return match($this->dietary_preference) {
            'veg_only' => 'Vegetarian Only',
            'veg_nonveg' => 'Vegetarian + Non-Vegetarian',
            'jain_food' => 'Jain Food',
            'all' => 'All Types',
            default => 'Not Specified'
        };
    }

    public function getChefTypeTextAttribute(): string
    {
        return match($this->chef_type) {
            'home_cook' => 'Home Cook',
            'event_cook' => 'Event Cook',
            'specialist_cook' => 'Specialist Cook',
            default => 'Not Specified'
        };
    }
}