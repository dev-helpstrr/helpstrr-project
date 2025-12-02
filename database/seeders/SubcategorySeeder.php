<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chefCategory = Category::where('slug', 'chef')->first();
        $driverCategory = Category::where('slug', 'driver')->first();
        $houseHelpCategory = Category::where('slug', 'house-help')->first();

        // Chef Subcategories
        $chefSubcategories = [
            [
                'category_id' => $chefCategory->id,
                'name' => 'Home Chef',
                'slug' => 'home-chef',
                'description' => 'Professional chef for home cooking',
                'hourly_rate' => 500.00,
                'min_hours' => 2,
                'consultation_fee' => 100.00,
                'pax_required' => true,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Party Chef',
                'slug' => 'party-chef',
                'description' => 'Chef for parties and events',
                'hourly_rate' => 800.00,
                'min_hours' => 4,
                'consultation_fee' => 200.00,
                'pax_required' => true,
                'recurrence_allowed' => false,
                'is_event_category' => true,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Tiffin Chef',
                'slug' => 'tiffin-chef',
                'description' => 'Regular meal preparation service',
                'hourly_rate' => 300.00,
                'min_hours' => 1,
                'consultation_fee' => 50.00,
                'pax_required' => true,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Specialty Chef',
                'slug' => 'specialty-chef',
                'description' => 'Chef specialized in specific cuisines',
                'hourly_rate' => 1000.00,
                'min_hours' => 3,
                'consultation_fee' => 300.00,
                'pax_required' => true,
                'recurrence_allowed' => false,
                'is_event_category' => true,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        // Driver Subcategories
        $driverSubcategories = [
            [
                'category_id' => $driverCategory->id,
                'name' => 'Personal Driver',
                'slug' => 'personal-driver',
                'description' => 'Personal driving service',
                'hourly_rate' => 200.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'pax_required' => false,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $driverCategory->id,
                'name' => 'Outstation Driver',
                'slug' => 'outstation-driver',
                'description' => 'Long distance driving service',
                'hourly_rate' => 300.00,
                'min_hours' => 8,
                'consultation_fee' => 100.00,
                'pax_required' => false,
                'recurrence_allowed' => false,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $driverCategory->id,
                'name' => 'Event Driver',
                'slug' => 'event-driver',
                'description' => 'Driver for events and functions',
                'hourly_rate' => 400.00,
                'min_hours' => 4,
                'consultation_fee' => 50.00,
                'pax_required' => false,
                'recurrence_allowed' => false,
                'is_event_category' => true,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        // House Help Subcategories
        $houseHelpSubcategories = [
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'House Cleaning',
                'slug' => 'house-cleaning',
                'description' => 'Complete house cleaning service',
                'hourly_rate' => 150.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'pax_required' => false,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'Deep Cleaning',
                'slug' => 'deep-cleaning',
                'description' => 'Thorough deep cleaning service',
                'hourly_rate' => 250.00,
                'min_hours' => 4,
                'consultation_fee' => 100.00,
                'pax_required' => false,
                'recurrence_allowed' => false,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'Babysitting',
                'slug' => 'babysitting',
                'description' => 'Professional babysitting service',
                'hourly_rate' => 200.00,
                'min_hours' => 3,
                'consultation_fee' => 50.00,
                'pax_required' => true,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'Elder Care',
                'slug' => 'elder-care',
                'description' => 'Professional elder care service',
                'hourly_rate' => 300.00,
                'min_hours' => 4,
                'consultation_fee' => 100.00,
                'pax_required' => true,
                'recurrence_allowed' => true,
                'is_event_category' => false,
                'is_takeaway' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        // Create all subcategories
        foreach ($chefSubcategories as $subcategory) {
            Subcategory::create($subcategory);
        }

        foreach ($driverSubcategories as $subcategory) {
            Subcategory::create($subcategory);
        }

        foreach ($houseHelpSubcategories as $subcategory) {
            Subcategory::create($subcategory);
        }
    }
}