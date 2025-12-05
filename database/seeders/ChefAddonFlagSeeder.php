<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChefAddonFlag;

class ChefAddonFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addonFlags = [
            [
                'name' => 'Meal Prep Service',
                'slug' => 'meal-prep-service',
                'description' => 'Chef prepares meals in advance for the week',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Recipe Teaching',
                'slug' => 'recipe-teaching',
                'description' => 'Chef teaches you how to cook the dishes',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Grocery Shopping',
                'slug' => 'grocery-shopping',
                'description' => 'Chef handles all grocery shopping for ingredients',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Kitchen Organization',
                'slug' => 'kitchen-organization',
                'description' => 'Chef organizes kitchen and cooking utensils',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Spice Grinding',
                'slug' => 'spice-grinding',
                'description' => 'Fresh spice grinding and masala preparation',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Marination Service',
                'slug' => 'marination-service',
                'description' => 'Advanced marination techniques for enhanced flavors',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Dessert Preparation',
                'slug' => 'dessert-preparation',
                'description' => 'Traditional and modern dessert preparation',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Bread Making',
                'slug' => 'bread-making',
                'description' => 'Fresh bread, roti, naan, and other bread items',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Pickle & Preserve Making',
                'slug' => 'pickle-preserve-making',
                'description' => 'Traditional pickles and preserves preparation',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Special Occasion Cooking',
                'slug' => 'special-occasion-cooking',
                'description' => 'Festival and special occasion traditional cooking',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Healthy Cooking',
                'slug' => 'healthy-cooking',
                'description' => 'Low oil, steamed, and health-conscious cooking methods',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Baby Food Preparation',
                'slug' => 'baby-food-preparation',
                'description' => 'Nutritious and safe baby food preparation',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Diet Meal Planning',
                'slug' => 'diet-meal-planning',
                'description' => 'Customized meal planning for specific diets',
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Fermentation Specialist',
                'slug' => 'fermentation-specialist',
                'description' => 'Traditional fermented foods like idli, dosa batter',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'Tandoor Cooking',
                'slug' => 'tandoor-cooking',
                'description' => 'Traditional tandoor-style cooking techniques',
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => 'Regional Specialty',
                'slug' => 'regional-specialty',
                'description' => 'Expert in specific regional cuisine specialties',
                'is_active' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'Bulk Cooking',
                'slug' => 'bulk-cooking',
                'description' => 'Large quantity cooking for events and gatherings',
                'is_active' => true,
                'sort_order' => 17,
            ],
            [
                'name' => 'Tiffin Service',
                'slug' => 'tiffin-service',
                'description' => 'Daily tiffin preparation and packaging',
                'is_active' => true,
                'sort_order' => 18,
            ],
            [
                'name' => 'Party Snacks',
                'slug' => 'party-snacks',
                'description' => 'Variety of party snacks and appetizers',
                'is_active' => true,
                'sort_order' => 19,
            ],
            [
                'name' => 'Beverage Preparation',
                'slug' => 'beverage-preparation',
                'description' => 'Traditional drinks, lassi, and beverage preparation',
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($addonFlags as $flag) {
            ChefAddonFlag::create($flag);
        }
    }
}