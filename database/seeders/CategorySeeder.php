<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Chef',
                'slug' => 'chef',
                'description' => 'Professional cooking services for all occasions',
                'night_multiplier' => 1.50, // 50% surcharge for night bookings
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'description' => 'Professional driving services',
                'night_multiplier' => 1.25, // 25% surcharge for night bookings
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'House Help',
                'slug' => 'house-help',
                'description' => 'Domestic help and cleaning services',
                'night_multiplier' => 1.30, // 30% surcharge for night bookings
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}