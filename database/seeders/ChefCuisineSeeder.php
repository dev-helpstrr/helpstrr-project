<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChefCuisine;

class ChefCuisineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuisines = [
            // Indian Cuisines
            [
                'name' => 'North Indian',
                'slug' => 'north-indian',
                'description' => 'Traditional North Indian cuisine including roti, dal, sabzi',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'South Indian',
                'slug' => 'south-indian',
                'description' => 'Authentic South Indian dishes like dosa, idli, sambar',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Bengali',
                'slug' => 'bengali',
                'description' => 'Traditional Bengali cuisine with fish, rice, and sweets',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Gujarati',
                'slug' => 'gujarati',
                'description' => 'Gujarati thali with dhokla, khandvi, and traditional sweets',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Punjabi',
                'slug' => 'punjabi',
                'description' => 'Rich Punjabi cuisine with butter chicken, naan, lassi',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Maharashtrian',
                'slug' => 'maharashtrian',
                'description' => 'Traditional Maharashtrian dishes like vada pav, misal pav',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Rajasthani',
                'slug' => 'rajasthani',
                'description' => 'Royal Rajasthani cuisine with dal baati churma, gatte ki sabzi',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Tamil',
                'slug' => 'tamil',
                'description' => 'Traditional Tamil cuisine with chettinad spices',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Kerala',
                'slug' => 'kerala',
                'description' => 'Kerala cuisine with coconut, seafood, and spices',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Hyderabadi',
                'slug' => 'hyderabadi',
                'description' => 'Hyderabadi biryani and Nizami cuisine',
                'is_active' => true,
                'sort_order' => 10,
            ],

            // International Cuisines
            [
                'name' => 'Chinese',
                'slug' => 'chinese',
                'description' => 'Indo-Chinese and authentic Chinese dishes',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Italian',
                'slug' => 'italian',
                'description' => 'Italian pasta, pizza, and Mediterranean dishes',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Continental',
                'slug' => 'continental',
                'description' => 'European continental cuisine',
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Mexican',
                'slug' => 'mexican',
                'description' => 'Mexican tacos, burritos, and spicy dishes',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'Thai',
                'slug' => 'thai',
                'description' => 'Thai curry, pad thai, and Asian flavors',
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => 'Japanese',
                'slug' => 'japanese',
                'description' => 'Japanese sushi, ramen, and traditional dishes',
                'is_active' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'Mediterranean',
                'slug' => 'mediterranean',
                'description' => 'Healthy Mediterranean cuisine with olive oil and herbs',
                'is_active' => true,
                'sort_order' => 17,
            ],
            [
                'name' => 'American',
                'slug' => 'american',
                'description' => 'American burgers, steaks, and comfort food',
                'is_active' => true,
                'sort_order' => 18,
            ],

            // Special Categories
            [
                'name' => 'Fusion',
                'slug' => 'fusion',
                'description' => 'Creative fusion of different cuisines',
                'is_active' => true,
                'sort_order' => 19,
            ],
            [
                'name' => 'Street Food',
                'slug' => 'street-food',
                'description' => 'Popular Indian street food items',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Desserts & Sweets',
                'slug' => 'desserts-sweets',
                'description' => 'Traditional and modern desserts and sweets',
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'name' => 'Healthy & Diet',
                'slug' => 'healthy-diet',
                'description' => 'Healthy, low-calorie, and diet-friendly meals',
                'is_active' => true,
                'sort_order' => 22,
            ],
            [
                'name' => 'Kids Special',
                'slug' => 'kids-special',
                'description' => 'Kid-friendly meals and snacks',
                'is_active' => true,
                'sort_order' => 23,
            ],
            [
                'name' => 'Breakfast Special',
                'slug' => 'breakfast-special',
                'description' => 'Variety of breakfast options from different regions',
                'is_active' => true,
                'sort_order' => 24,
            ],
        ];

        foreach ($cuisines as $cuisine) {
            ChefCuisine::create($cuisine);
        }
    }
}