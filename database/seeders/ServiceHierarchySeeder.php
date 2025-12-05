<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\Service;

class ServiceHierarchySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create Categories
        $homeServices = NewCategory::create([
            'name' => 'Home Services',
            'slug' => 'home-services',
            'description' => 'Professional home maintenance and cleaning services',
            'icon' => 'heroicon-o-home',
            'color' => '#3B82F6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $foodServices = NewCategory::create([
            'name' => 'Food & Catering',
            'slug' => 'food-catering',
            'description' => 'Professional cooking and catering services',
            'icon' => 'heroicon-o-cake',
            'color' => '#EF4444',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $transportServices = NewCategory::create([
            'name' => 'Transport & Delivery',
            'slug' => 'transport-delivery',
            'description' => 'Transportation and delivery services',
            'icon' => 'heroicon-o-truck',
            'color' => '#10B981',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create Subcategories
        $houseKeeping = NewSubcategory::create([
            'name' => 'House Keeping',
            'slug' => 'house-keeping',
            'description' => 'Professional house cleaning and maintenance',
            'icon' => 'heroicon-o-home-modern',
            'color' => '#3B82F6',
            'hourly_rate' => 150.00,
            'min_hours' => 2,
            'consultation_fee' => 0.00,
            'pax_required' => false,
            'recurrence_allowed' => true,
            'is_event_category' => false,
            'is_takeaway' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $babySitting = NewSubcategory::create([
            'name' => 'Baby Sitting',
            'slug' => 'baby-sitting',
            'description' => 'Professional child care services',
            'icon' => 'heroicon-o-heart',
            'color' => '#F59E0B',
            'hourly_rate' => 200.00,
            'min_hours' => 3,
            'consultation_fee' => 50.00,
            'pax_required' => true,
            'recurrence_allowed' => true,
            'is_event_category' => false,
            'is_takeaway' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $personalChef = NewSubcategory::create([
            'name' => 'Personal Chef',
            'slug' => 'personal-chef',
            'description' => 'Professional cooking services at your location',
            'icon' => 'heroicon-o-user',
            'color' => '#EF4444',
            'hourly_rate' => 300.00,
            'min_hours' => 2,
            'consultation_fee' => 100.00,
            'pax_required' => true,
            'recurrence_allowed' => true,
            'is_event_category' => true,
            'is_takeaway' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $cateringServices = NewSubcategory::create([
            'name' => 'Catering Services',
            'slug' => 'catering-services',
            'description' => 'Large scale event catering',
            'icon' => 'heroicon-o-cake',
            'color' => '#EF4444',
            'hourly_rate' => 500.00,
            'min_hours' => 4,
            'consultation_fee' => 200.00,
            'pax_required' => true,
            'recurrence_allowed' => false,
            'is_event_category' => true,
            'is_takeaway' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $personalDriver = NewSubcategory::create([
            'name' => 'Personal Driver',
            'slug' => 'personal-driver',
            'description' => 'Professional driving services',
            'icon' => 'heroicon-o-user',
            'color' => '#10B981',
            'hourly_rate' => 250.00,
            'min_hours' => 2,
            'consultation_fee' => 0.00,
            'pax_required' => false,
            'recurrence_allowed' => true,
            'is_event_category' => false,
            'is_takeaway' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $deliveryServices = NewSubcategory::create([
            'name' => 'Delivery Services',
            'slug' => 'delivery-services',
            'description' => 'Package and item delivery',
            'icon' => 'heroicon-o-truck',
            'color' => '#10B981',
            'hourly_rate' => 100.00,
            'min_hours' => 1,
            'consultation_fee' => 0.00,
            'pax_required' => false,
            'recurrence_allowed' => true,
            'is_event_category' => false,
            'is_takeaway' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create Services
        $deepCleaning = Service::create([
            'name' => 'Deep Cleaning',
            'slug' => 'deep-cleaning',
            'description' => 'Comprehensive deep cleaning service for your entire home',
            'short_description' => 'Complete home deep cleaning',
            'icon' => 'heroicon-o-sparkles',
            'base_price' => null,
            'hourly_rate' => 150.00,
            'min_hours' => 3,
            'max_hours' => 8,
            'consultation_fee' => 0.00,
            'travel_allowance' => 50.00,
            'pax_required' => false,
            'min_pax' => 1,
            'max_pax' => null,
            'recurrence_allowed' => true,
            'is_event_service' => false,
            'is_takeaway' => false,
            'requires_verification' => false,
            'is_active' => true,
            'sort_order' => 1,
            'requirements' => [
                'cleaning_supplies' => 'Customer to provide basic cleaning supplies',
                'access' => 'Full access to all areas to be cleaned',
            ],
            'features' => [
                'deep_clean' => 'Thorough cleaning of all surfaces',
                'sanitization' => 'Sanitization of high-touch areas',
                'organization' => 'Basic organization of items',
            ],
        ]);

        $regularCleaning = Service::create([
            'name' => 'Regular Cleaning',
            'slug' => 'regular-cleaning',
            'description' => 'Regular maintenance cleaning for your home',
            'short_description' => 'Weekly/monthly home cleaning',
            'icon' => 'heroicon-o-home',
            'base_price' => null,
            'hourly_rate' => 120.00,
            'min_hours' => 2,
            'max_hours' => 6,
            'consultation_fee' => 0.00,
            'travel_allowance' => 30.00,
            'pax_required' => false,
            'min_pax' => 1,
            'max_pax' => null,
            'recurrence_allowed' => true,
            'is_event_service' => false,
            'is_takeaway' => false,
            'requires_verification' => false,
            'is_active' => true,
            'sort_order' => 2,
            'requirements' => [
                'supplies' => 'Basic cleaning supplies provided by customer',
            ],
            'features' => [
                'maintenance' => 'Regular maintenance cleaning',
                'flexible' => 'Flexible scheduling options',
            ],
        ]);

        $infantCare = Service::create([
            'name' => 'Infant Care',
            'slug' => 'infant-care',
            'description' => 'Specialized care for infants and toddlers',
            'short_description' => 'Professional infant care services',
            'icon' => 'heroicon-o-heart',
            'base_price' => null,
            'hourly_rate' => 250.00,
            'min_hours' => 4,
            'max_hours' => 12,
            'consultation_fee' => 100.00,
            'travel_allowance' => 0.00,
            'pax_required' => true,
            'min_pax' => 1,
            'max_pax' => 3,
            'recurrence_allowed' => true,
            'is_event_service' => false,
            'is_takeaway' => false,
            'requires_verification' => true,
            'is_active' => true,
            'sort_order' => 1,
            'requirements' => [
                'certification' => 'Certified in infant care',
                'background_check' => 'Background verification required',
            ],
            'features' => [
                'specialized' => 'Specialized infant care training',
                'emergency' => 'Emergency response trained',
            ],
        ]);

        $indianCuisine = Service::create([
            'name' => 'Indian Cuisine Cooking',
            'slug' => 'indian-cuisine-cooking',
            'description' => 'Authentic Indian cuisine prepared by professional chefs',
            'short_description' => 'Professional Indian cooking',
            'icon' => 'heroicon-o-fire',
            'base_price' => null,
            'hourly_rate' => 350.00,
            'min_hours' => 2,
            'max_hours' => 8,
            'consultation_fee' => 150.00,
            'travel_allowance' => 100.00,
            'pax_required' => true,
            'min_pax' => 2,
            'max_pax' => 20,
            'recurrence_allowed' => true,
            'is_event_service' => true,
            'is_takeaway' => false,
            'requires_verification' => false,
            'is_active' => true,
            'sort_order' => 1,
            'requirements' => [
                'ingredients' => 'Fresh ingredients to be provided',
                'kitchen_access' => 'Full kitchen access required',
            ],
            'features' => [
                'authentic' => 'Authentic Indian recipes',
                'customizable' => 'Menu customization available',
            ],
        ]);

        $eventCatering = Service::create([
            'name' => 'Event Catering',
            'slug' => 'event-catering',
            'description' => 'Full-service catering for events and parties',
            'short_description' => 'Professional event catering',
            'icon' => 'heroicon-o-cake',
            'base_price' => 5000.00,
            'hourly_rate' => null,
            'min_hours' => 6,
            'max_hours' => 12,
            'consultation_fee' => 500.00,
            'travel_allowance' => 200.00,
            'pax_required' => true,
            'min_pax' => 20,
            'max_pax' => 500,
            'recurrence_allowed' => false,
            'is_event_service' => true,
            'is_takeaway' => true,
            'requires_verification' => true,
            'is_active' => true,
            'sort_order' => 1,
            'requirements' => [
                'advance_booking' => '7 days advance booking required',
                'venue_access' => 'Venue access for setup required',
            ],
            'features' => [
                'full_service' => 'Complete catering service',
                'setup' => 'Event setup and cleanup included',
            ],
        ]);

        $airportTransfer = Service::create([
            'name' => 'Airport Transfer',
            'slug' => 'airport-transfer',
            'description' => 'Reliable airport pickup and drop-off services',
            'short_description' => 'Airport transportation service',
            'icon' => 'heroicon-o-paper-airplane',
            'base_price' => 800.00,
            'hourly_rate' => null,
            'min_hours' => 1,
            'max_hours' => 3,
            'consultation_fee' => 0.00,
            'travel_allowance' => 0.00,
            'pax_required' => false,
            'min_pax' => 1,
            'max_pax' => 4,
            'recurrence_allowed' => true,
            'is_event_service' => false,
            'is_takeaway' => false,
            'requires_verification' => false,
            'is_active' => true,
            'sort_order' => 1,
            'requirements' => [
                'flight_details' => 'Flight details required',
                'advance_booking' => '2 hours advance booking',
            ],
            'features' => [
                'reliable' => 'Punctual and reliable service',
                'tracking' => 'Real-time tracking available',
            ],
        ]);

        // Create Category-Subcategory relationships
        $homeServices->subcategories()->attach($houseKeeping->id, ['is_primary' => true, 'sort_order' => 1]);
        $homeServices->subcategories()->attach($babySitting->id, ['is_primary' => true, 'sort_order' => 2]);

        $foodServices->subcategories()->attach($personalChef->id, ['is_primary' => true, 'sort_order' => 1]);
        $foodServices->subcategories()->attach($cateringServices->id, ['is_primary' => true, 'sort_order' => 2]);

        $transportServices->subcategories()->attach($personalDriver->id, ['is_primary' => true, 'sort_order' => 1]);
        $transportServices->subcategories()->attach($deliveryServices->id, ['is_primary' => true, 'sort_order' => 2]);

        // Create Subcategory-Service relationships
        $houseKeeping->services()->attach($deepCleaning->id, ['is_primary' => true, 'sort_order' => 1]);
        $houseKeeping->services()->attach($regularCleaning->id, ['is_primary' => false, 'sort_order' => 2]);

        $babySitting->services()->attach($infantCare->id, ['is_primary' => true, 'sort_order' => 1]);

        $personalChef->services()->attach($indianCuisine->id, ['is_primary' => true, 'sort_order' => 1]);

        $cateringServices->services()->attach($eventCatering->id, ['is_primary' => true, 'sort_order' => 1]);

        $personalDriver->services()->attach($airportTransfer->id, ['is_primary' => true, 'sort_order' => 1]);

        $this->command->info('Service hierarchy seeded successfully!');
    }
}