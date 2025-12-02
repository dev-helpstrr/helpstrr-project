<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OptionalFlag;
use App\Models\Category;

class OptionalFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Optional Flags (applicable across categories)
        $optionalFlags = [
            [
                'name' => 'Bring Own Ingredients',
                'slug' => 'bring-own-ingredients',
                'description' => 'Chef will bring all required ingredients',
                'price_modifier' => 25.00, // 25% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Cleanup Service',
                'slug' => 'cleanup-service',
                'description' => 'Chef will clean kitchen after cooking',
                'price_modifier' => 15.00, // 15% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Serving Staff',
                'slug' => 'serving-staff',
                'description' => 'Additional staff for serving food',
                'price_modifier' => 30.00, // 30% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium Utensils',
                'slug' => 'premium-utensils',
                'description' => 'Chef brings premium cooking utensils',
                'price_modifier' => 10.00, // 10% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Live Cooking Demo',
                'slug' => 'live-cooking-demo',
                'description' => 'Interactive cooking demonstration',
                'price_modifier' => 50.00, // 50% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Organic Ingredients Only',
                'slug' => 'organic-ingredients-only',
                'description' => 'Use only certified organic ingredients',
                'price_modifier' => 40.00, // 40% increase
                'is_hard_filter' => true, // Hard filter - must be supported by chef
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Menu Planning',
                'slug' => 'menu-planning',
                'description' => 'Chef provides detailed menu planning service',
                'price_modifier' => 20.00, // 20% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Food Photography',
                'slug' => 'food-photography',
                'description' => 'Professional food photography service',
                'price_modifier' => 35.00, // 35% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'AC Car Required',
                'slug' => 'ac-car-required',
                'description' => 'Air-conditioned vehicle required',
                'price_modifier' => 20.00, // 20% increase
                'is_hard_filter' => true,
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Luxury Vehicle',
                'slug' => 'luxury-vehicle',
                'description' => 'Premium luxury vehicle',
                'price_modifier' => 50.00, // 50% increase
                'is_hard_filter' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'English Speaking',
                'slug' => 'english-speaking',
                'description' => 'Service provider must speak English fluently',
                'price_modifier' => 15.00, // 15% increase
                'is_hard_filter' => true,
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Fuel Included',
                'slug' => 'fuel-included',
                'description' => 'Fuel cost included in service',
                'price_modifier' => 30.00, // 30% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Toll Included',
                'slug' => 'toll-included',
                'description' => 'Toll charges included in service',
                'price_modifier' => 25.00, // 25% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Bring Own Supplies',
                'slug' => 'bring-own-supplies',
                'description' => 'Helper brings all cleaning supplies',
                'price_modifier' => 20.00, // 20% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'Eco-Friendly Products',
                'slug' => 'eco-friendly-products',
                'description' => 'Use only eco-friendly cleaning products',
                'price_modifier' => 25.00, // 25% increase
                'is_hard_filter' => true,
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => 'Pet Friendly',
                'slug' => 'pet-friendly',
                'description' => 'Comfortable working around pets',
                'price_modifier' => 10.00, // 10% increase
                'is_hard_filter' => true,
                'is_active' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'Laundry Service',
                'slug' => 'laundry-service',
                'description' => 'Include washing and ironing clothes',
                'price_modifier' => 30.00, // 30% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 17,
            ],
            [
                'name' => 'Sanitization Service',
                'slug' => 'sanitization-service',
                'description' => 'Complete sanitization and disinfection',
                'price_modifier' => 40.00, // 40% increase
                'is_hard_filter' => false,
                'is_active' => true,
                'sort_order' => 18,
            ],
        ];

        // Create all optional flags
        foreach ($optionalFlags as $flag) {
            OptionalFlag::create($flag);
        }
    }
}