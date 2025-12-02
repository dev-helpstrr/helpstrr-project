<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DietaryPreference;

class DietaryPreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $preferences = [
            [
                'name' => 'Pure Vegetarian',
                'slug' => 'pure-vegetarian',
                'description' => 'Strictly vegetarian with no onion, garlic, or non-veg ingredients',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Vegetarian',
                'slug' => 'vegetarian',
                'description' => 'Vegetarian food including dairy products',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Vegan',
                'slug' => 'vegan',
                'description' => 'Plant-based diet with no animal products',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Jain Food',
                'slug' => 'jain-food',
                'description' => 'Jain dietary restrictions - no root vegetables, onion, garlic',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Eggetarian',
                'slug' => 'eggetarian',
                'description' => 'Vegetarian diet that includes eggs',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Non-Vegetarian',
                'slug' => 'non-vegetarian',
                'description' => 'Includes all types of meat, poultry, and seafood',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Halal',
                'slug' => 'halal',
                'description' => 'Halal certified meat and ingredients only',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Kosher',
                'slug' => 'kosher',
                'description' => 'Kosher dietary laws compliant food',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Gluten-Free',
                'slug' => 'gluten-free',
                'description' => 'No wheat, barley, rye, or gluten-containing ingredients',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Keto',
                'slug' => 'keto',
                'description' => 'High-fat, low-carb ketogenic diet',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Paleo',
                'slug' => 'paleo',
                'description' => 'Paleolithic diet - no processed foods, grains, or dairy',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Low Sodium',
                'slug' => 'low-sodium',
                'description' => 'Reduced salt content for health reasons',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Diabetic Friendly',
                'slug' => 'diabetic-friendly',
                'description' => 'Low sugar, controlled carbohydrate meals',
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Heart Healthy',
                'slug' => 'heart-healthy',
                'description' => 'Low cholesterol, low saturated fat meals',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'High Protein',
                'slug' => 'high-protein',
                'description' => 'Protein-rich meals for fitness and health',
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => 'Raw Food',
                'slug' => 'raw-food',
                'description' => 'Uncooked, unprocessed plant foods',
                'is_active' => true,
                'sort_order' => 16,
            ],
        ];

        foreach ($preferences as $preference) {
            DietaryPreference::create($preference);
        }
    }
}