<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Task;
use App\Models\SPUser;
use App\Models\Service;
use App\Models\Customer;
use App\Models\ChefCuisine;
use App\Models\NewCategory;
use Illuminate\Support\Str;
use App\Models\OptionalFlag;
use App\Models\SpCapability;
use App\Models\ChefAddonFlag;
use App\Models\NewSubcategory;
use App\Models\CustomerAddress;
use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;
use App\Models\DietaryPreference;
use App\Models\SpAddonCapability;
use App\Models\TaskPriceComponent;
use App\Models\SpCuisineCapability;
use App\Models\SpDietaryCapability;
use App\Models\SpOptionalCapability;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class SPSearchTestDataSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();
        $this->command->info('Creating comprehensive SP Search test data...');

        // Create base data first
        $this->createCategories();
        $this->createSubcategories();
        $this->createServices();
        $this->createChefCuisines();
        $this->createDietaryPreferences();
        $this->createAddonFlags();
        $this->createOptionalFlags();

        // Create customers with addresses
        $this->createCustomers();

        // Create service providers with all capabilities
        $this->createServiceProviders();

        // Create sample tasks for testing
        $this->createSampleTasks();

        $this->command->info('SP Search test data created successfully!');
    }

    private function createCategories(): void
    {
        $this->command->info('Creating categories...');

        $categories = [
            [
                'name' => 'Chef',
                'slug' => 'chef',
                'description' => 'Professional cooking services',
                'icon' => 'chef-hat',
                'color' => '#FF6B6B',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'House Help',
                'slug' => 'house-help',
                'description' => 'Home cleaning and maintenance',
                'icon' => 'home',
                'color' => '#4ECDC4',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'description' => 'Transportation services',
                'icon' => 'car',
                'color' => '#45B7D1',
                'is_active' => true,
                'sort_order' => 3,
            ]
        ];

        foreach ($categories as $categoryData) {
            NewCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }
    }

    private function createSubcategories(): void
    {
        $this->command->info('Creating subcategories...');

        $chefCategory = NewCategory::where('slug', 'chef')->first();
        $houseHelpCategory = NewCategory::where('slug', 'house-help')->first();
        $driverCategory = NewCategory::where('slug', 'driver')->first();

        $subcategories = [
            // Chef subcategories
            [
                'category_id' => $chefCategory->id,
                'name' => 'Breakfast',
                'slug' => 'breakfast',
                'description' => 'Breakfast preparation',
                'hourly_rate' => 200.00,
                'min_hours' => 1,
                'consultation_fee' => 0.00,
                'pax_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Lunch',
                'slug' => 'lunch',
                'description' => 'Lunch preparation',
                'hourly_rate' => 250.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'pax_required' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Dinner',
                'slug' => 'dinner',
                'description' => 'Dinner preparation',
                'hourly_rate' => 250.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'pax_required' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'category_id' => $chefCategory->id,
                'name' => 'Party/Event Cooking',
                'slug' => 'party-event-cooking',
                'description' => 'Party and event cooking',
                'hourly_rate' => 350.00,
                'min_hours' => 4,
                'consultation_fee' => 49.00,
                'pax_required' => true,
                'is_event_category' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
            // House Help subcategories
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'Utensils Cleaning',
                'slug' => 'utensils-cleaning',
                'description' => 'Kitchen utensils cleaning',
                'hourly_rate' => 150.00,
                'min_hours' => 1,
                'consultation_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $houseHelpCategory->id,
                'name' => 'Deep Cleaning Support',
                'slug' => 'deep-cleaning-support',
                'description' => 'Deep cleaning assistance',
                'hourly_rate' => 250.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            // Driver subcategories
            [
                'category_id' => $driverCategory->id,
                'name' => 'Local Trip',
                'slug' => 'local-trip',
                'description' => 'Local city trips',
                'hourly_rate' => 160.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $driverCategory->id,
                'name' => 'Airport Pickup/Drop',
                'slug' => 'airport-pickup-drop',
                'description' => 'Airport transportation',
                'hourly_rate' => 180.00,
                'min_hours' => 2,
                'consultation_fee' => 0.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($subcategories as $subcategoryData) {
            // 1. pull out category_id before saving the subcategory
            $categoryId = $subcategoryData['category_id'] ?? null;
            unset($subcategoryData['category_id']);

            // 2. create/update the subcategory itself
            $subcategory = NewSubcategory::updateOrCreate(
                ['slug' => $subcategoryData['slug']],
                $subcategoryData
            );

            // 3. attach it to the category via the pivot table
            if ($categoryId && $subcategory) {
                $category = NewCategory::find($categoryId);

                if ($category) {
                    $category->subcategories()->syncWithoutDetaching([
                        $subcategory->id => [
                            'is_primary' => true,
                            'sort_order' => $subcategoryData['sort_order'] ?? 0,
                        ],
                    ]);
                }
            }
        }
    }

    private function createServices(): void
    {
        $this->command->info('Creating services...');

        $services = [
            [
                'name' => 'Home Style Cooking',
                'slug' => 'home-style-cooking',
                'description' => 'Traditional home style cooking',
                'base_price' => 300.00,
                'is_active' => true,
            ],
            [
                'name' => 'Gourmet Cooking',
                'slug' => 'gourmet-cooking',
                'description' => 'Professional gourmet cooking',
                'base_price' => 500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Basic Cleaning',
                'slug' => 'basic-cleaning',
                'description' => 'Basic house cleaning',
                'base_price' => 200.00,
                'is_active' => true,
            ],
            [
                'name' => 'Personal Driving',
                'slug' => 'personal-driving',
                'description' => 'Personal driver service',
                'base_price' => 250.00,
                'is_active' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }
    }

    private function createChefCuisines(): void
    {
        $this->command->info('Creating chef cuisines...');

        $cuisines = [
            ['name' => 'North Indian', 'description' => 'Traditional North Indian cuisine', 'is_active' => true],
            ['name' => 'South Indian', 'description' => 'Traditional South Indian cuisine', 'is_active' => true],
            ['name' => 'Bengali', 'description' => 'Traditional Bengali cuisine', 'is_active' => true],
            ['name' => 'Gujarati', 'description' => 'Traditional Gujarati cuisine', 'is_active' => true],
            ['name' => 'Punjabi', 'description' => 'Traditional Punjabi cuisine', 'is_active' => true],
            ['name' => 'Chinese', 'description' => 'Chinese cuisine', 'is_active' => true],
            ['name' => 'Continental', 'description' => 'Continental cuisine', 'is_active' => true],
            ['name' => 'Italian', 'description' => 'Italian cuisine', 'is_active' => true],
            ['name' => 'Mexican', 'description' => 'Mexican cuisine', 'is_active' => true],
            ['name' => 'Thai', 'description' => 'Thai cuisine', 'is_active' => true],
        ];

        foreach ($cuisines as $cuisineData) {
            ChefCuisine::updateOrCreate(
                ['name' => $cuisineData['name']],
                $cuisineData
            );
        }
    }

    private function createDietaryPreferences(): void
    {
        $this->command->info('Creating dietary preferences...');

        $preferences = [
            ['name' => 'Vegetarian', 'description' => 'Pure vegetarian food', 'is_active' => true],
            ['name' => 'Non-Vegetarian', 'description' => 'Non-vegetarian food', 'is_active' => true],
            ['name' => 'Jain', 'description' => 'Jain dietary requirements', 'is_active' => true],
            ['name' => 'Vegan', 'description' => 'Vegan food only', 'is_active' => true],
        ];

        foreach ($preferences as $preferenceData) {
            // ensure slug is always set
            $preferenceData['slug'] = Str::slug($preferenceData['name']);

            DietaryPreference::updateOrCreate(
                ['slug' => $preferenceData['slug']], // use slug as the unique key
                $preferenceData
            );
        }
    }


    private function createAddonFlags(): void
    {
        $this->command->info('Creating addon flags...');

        $flags = [
            ['name' => 'Patient Diet', 'description' => 'Special diet for patients', 'is_active' => true],
            ['name' => 'Kids Compatible', 'description' => 'Child-friendly cooking', 'is_active' => true],
            ['name' => 'Elderly Compatible', 'description' => 'Suitable for elderly', 'is_active' => true],
            ['name' => 'Healthy/Diet Food', 'description' => 'Health-conscious cooking', 'is_active' => true],
            ['name' => 'No Onion', 'description' => 'Cooking without onion', 'is_active' => true],
            ['name' => 'No Garlic', 'description' => 'Cooking without garlic', 'is_active' => true],
        ];

        foreach ($flags as $flagData) {
            $flagData['slug'] = Str::slug($flagData['name']);
            ChefAddonFlag::updateOrCreate(
                ['slug' => $flagData['slug']], // use slug as the unique key
                $flagData
            );
        }
    }

    private function createOptionalFlags(): void
    {
        $this->command->info('Creating optional flags...');

        $flags = [
            ['name' => 'SP must be vegetarian', 'description' => 'Service provider must be vegetarian', 'is_hard_filter' => true, 'is_active' => true],
            ['name' => 'Certified experience', 'description' => 'Only certified SPs allowed', 'is_hard_filter' => true, 'is_active' => true],
            ['name' => 'Gold-level chef preference', 'description' => 'Prefer gold-level chefs', 'is_hard_filter' => false, 'is_active' => true],
            ['name' => 'Female SP preferred', 'description' => 'Prefer female service providers', 'is_hard_filter' => false, 'is_active' => true],
            ['name' => 'English speaking', 'description' => 'SP should speak English', 'is_hard_filter' => false, 'is_active' => true],
        ];

        foreach ($flags as $flagData) {
            $flagData['slug'] = Str::slug($flagData['name']);
            OptionalFlag::updateOrCreate(
                ['slug' => $flagData['slug']], // use slug as the unique key
                $flagData
            );
        }
    }

    private function createCustomers(): void
    {
        $this->command->info('Creating test customers...');

        $customers = [
            [
                'name' => 'Test Customer 1',
                'phone' => '9876543210',
                'email' => 'customer1@test.com',
                'password' => Hash::make('password123'),
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'addresses' => [
                    [
                        'type' => 'home',
                        'address_line_1' => '123 Test Street',
                        'city' => 'Kolkata',
                        'state' => 'West Bengal',
                        'pincode' => '700001',
                        'latitude' => 22.5726,
                        'longitude' => 88.3639,
                        'is_default' => true,
                    ]
                ]
            ],
            [
                'name' => 'Test Customer 2',
                'phone' => '9876543211',
                'email' => 'customer2@test.com',
                'password' => Hash::make('password123'),
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'addresses' => [
                    [
                        'type' => 'home',
                        'address_line_1' => '456 Test Avenue',
                        'city' => 'Kolkata',
                        'state' => 'West Bengal',
                        'pincode' => '700020',
                        'latitude' => 22.5448,
                        'longitude' => 88.3426,
                        'is_default' => true,
                    ]
                ]
            ]
        ];

        foreach ($customers as $customerData) {
            $addresses = $customerData['addresses'];
            unset($customerData['addresses']);

            $customer = Customer::updateOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );

            foreach ($addresses as $addressData) {
                CustomerAddress::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'type' => $addressData['type']
                    ],
                    array_merge($addressData, [
                        'customer_id' => $customer->id,
                        'country' => 'India'
                    ])
                );
            }
        }
    }

    private function createServiceProviders(): void
    {
        $this->command->info('Creating comprehensive service providers...');

        // Get reference data
        $chefCategory = NewCategory::where('slug', 'chef')->first();
        $houseHelpCategory = NewCategory::where('slug', 'house-help')->first();
        $driverCategory = NewCategory::where('slug', 'driver')->first();



        $cuisines = ChefCuisine::all();
        $dietaryPreferences = DietaryPreference::all();
        $addonFlags = ChefAddonFlag::all();
        $optionalFlags = OptionalFlag::all();

        // Create diverse service providers
        $spData = [
            // High-rated Chef SPs
            [
                'first_name' => 'Rajesh',
                'last_name' => 'Kumar',
                'phone' => '9876543220',
                'email' => 'rajesh.chef@test.com',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'address' => 'Salt Lake, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => true,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.8,
                    'total_ratings' => 45,
                    'acceptance_rate' => 95.0,
                    'punctuality_score' => 92.0,
                    'behaviour_score' => 96.0,
                    'is_gold_level' => true,
                    'cancellation_score' => 5.0,
                    'complaint_score' => 2.0,
                    'rejection_frequency' => 3,
                    'tasks_completed' => 42,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['breakfast', 'lunch', 'dinner'],
                'cuisines' => ['North Indian', 'Punjabi', 'Chinese'],
                'dietary_preferences' => ['Vegetarian', 'Jain'],
                'addon_flags' => ['Kids Compatible', 'Healthy/Diet Food', 'No Onion'],
                'optional_flags' => ['SP must be vegetarian', 'Gold-level chef preference'],
            ],
            [
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'phone' => '9876543221',
                'email' => 'priya.chef@test.com',
                'latitude' => 22.5448,
                'longitude' => 88.3426,
                'address' => 'Park Street, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => false,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.6,
                    'total_ratings' => 32,
                    'acceptance_rate' => 88.0,
                    'punctuality_score' => 89.0,
                    'behaviour_score' => 94.0,
                    'is_gold_level' => true,
                    'cancellation_score' => 8.0,
                    'complaint_score' => 3.0,
                    'rejection_frequency' => 5,
                    'tasks_completed' => 28,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['lunch', 'dinner', 'party-event-cooking'],
                'cuisines' => ['South Indian', 'Bengali', 'Continental'],
                'dietary_preferences' => ['Vegetarian', 'Non-Vegetarian'],
                'addon_flags' => ['Patient Diet', 'Elderly Compatible'],
                'optional_flags' => ['Certified experience', 'Female SP preferred'],
            ],
            // Medium-rated Chef SPs
            [
                'first_name' => 'Amit',
                'last_name' => 'Singh',
                'phone' => '9876543222',
                'email' => 'amit.chef@test.com',
                'latitude' => 22.5697,
                'longitude' => 88.3697,
                'address' => 'Ballygunge, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => true,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.2,
                    'total_ratings' => 18,
                    'acceptance_rate' => 82.0,
                    'punctuality_score' => 85.0,
                    'behaviour_score' => 88.0,
                    'is_gold_level' => false,
                    'cancellation_score' => 12.0,
                    'complaint_score' => 5.0,
                    'rejection_frequency' => 8,
                    'tasks_completed' => 15,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['breakfast', 'lunch'],
                'cuisines' => ['North Indian', 'Chinese', 'Italian'],
                'dietary_preferences' => ['Non-Vegetarian'],
                'addon_flags' => ['Kids Compatible'],
                'optional_flags' => ['English speaking'],
            ],
            // New Chef SPs (less than 5 ratings)
            [
                'first_name' => 'Neha',
                'last_name' => 'Patel',
                'phone' => '9876543223',
                'email' => 'neha.chef@test.com',
                'latitude' => 22.5355,
                'longitude' => 88.3209,
                'address' => 'Howrah, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => false,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.0,
                    'total_ratings' => 3,
                    'acceptance_rate' => 100.0,
                    'punctuality_score' => 90.0,
                    'behaviour_score' => 92.0,
                    'is_gold_level' => false,
                    'cancellation_score' => 0.0,
                    'complaint_score' => 0.0,
                    'rejection_frequency' => 0,
                    'tasks_completed' => 3,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['breakfast', 'lunch', 'dinner'],
                'cuisines' => ['Gujarati', 'North Indian'],
                'dietary_preferences' => ['Vegetarian', 'Jain'],
                'addon_flags' => ['No Onion', 'No Garlic'],
                'optional_flags' => ['SP must be vegetarian'],
            ],
            // House Help SPs
            [
                'first_name' => 'Sunita',
                'last_name' => 'Devi',
                'phone' => '9876543224',
                'email' => 'sunita.help@test.com',
                'latitude' => 22.5626,
                'longitude' => 88.3639,
                'address' => 'New Town, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => false,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.5,
                    'total_ratings' => 25,
                    'acceptance_rate' => 90.0,
                    'punctuality_score' => 88.0,
                    'behaviour_score' => 91.0,
                    'is_gold_level' => false,
                    'cancellation_score' => 7.0,
                    'complaint_score' => 4.0,
                    'rejection_frequency' => 6,
                    'tasks_completed' => 22,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['house-help'],
                'subcategories' => ['utensils-cleaning', 'deep-cleaning-support'],
                'cuisines' => [],
                'dietary_preferences' => [],
                'addon_flags' => [],
                'optional_flags' => ['Female SP preferred'],
            ],
            // Driver SPs
            [
                'first_name' => 'Ravi',
                'last_name' => 'Das',
                'phone' => '9876543225',
                'email' => 'ravi.driver@test.com',
                'latitude' => 22.5744,
                'longitude' => 88.3629,
                'address' => 'Dum Dum, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => true,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.3,
                    'total_ratings' => 38,
                    'acceptance_rate' => 85.0,
                    'punctuality_score' => 92.0,
                    'behaviour_score' => 89.0,
                    'is_gold_level' => false,
                    'cancellation_score' => 10.0,
                    'complaint_score' => 6.0,
                    'rejection_frequency' => 12,
                    'tasks_completed' => 32,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['driver'],
                'subcategories' => ['local-trip', 'airport-pickup-drop'],
                'cuisines' => [],
                'dietary_preferences' => [],
                'addon_flags' => [],
                'optional_flags' => ['English speaking'],
            ],
            // Far distance SP (for testing radius filters)
            [
                'first_name' => 'Deepak',
                'last_name' => 'Roy',
                'phone' => '9876543226',
                'email' => 'deepak.chef@test.com',
                'latitude' => 22.4707,
                'longitude' => 88.3378, // About 15km from center
                'address' => 'Baruipur, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => true,
                'can_work_nights' => true,
                'last_seen_at' => now(),
                'sp_data' => [
                    'rating' => 4.7,
                    'total_ratings' => 22,
                    'acceptance_rate' => 93.0,
                    'punctuality_score' => 95.0,
                    'behaviour_score' => 97.0,
                    'is_gold_level' => true,
                    'cancellation_score' => 3.0,
                    'complaint_score' => 1.0,
                    'rejection_frequency' => 2,
                    'tasks_completed' => 20,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['breakfast', 'lunch', 'dinner', 'party-event-cooking'],
                'cuisines' => ['Bengali', 'North Indian', 'South Indian'],
                'dietary_preferences' => ['Vegetarian', 'Non-Vegetarian'],
                'addon_flags' => ['Patient Diet', 'Elderly Compatible', 'Kids Compatible'],
                'optional_flags' => ['Certified experience', 'Gold-level chef preference'],
            ],
            // Offline SP (for testing online filter)
            [
                'first_name' => 'Offline',
                'last_name' => 'SP',
                'phone' => '9876543227',
                'email' => 'offline.sp@test.com',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'address' => 'Salt Lake, Kolkata',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'is_online' => false, // Offline
                'can_work_nights' => true,
                'last_seen_at' => now()->subHours(3),
                'sp_data' => [
                    'rating' => 4.9,
                    'total_ratings' => 50,
                    'acceptance_rate' => 98.0,
                    'punctuality_score' => 98.0,
                    'behaviour_score' => 99.0,
                    'is_gold_level' => true,
                    'cancellation_score' => 1.0,
                    'complaint_score' => 0.0,
                    'rejection_frequency' => 1,
                    'tasks_completed' => 49,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                    'is_active' => true,
                ],
                'categories' => ['chef'],
                'subcategories' => ['breakfast', 'lunch', 'dinner'],
                'cuisines' => ['North Indian', 'South Indian'],
                'dietary_preferences' => ['Vegetarian'],
                'addon_flags' => ['Kids Compatible'],
                'optional_flags' => ['SP must be vegetarian'],
            ],
        ];

        // First, create some customers for task assignments
        echo "Creating customers for task assignments...\n";
        $customers = [];
        $customerData = [
            ['name' => 'Rahul Sharma', 'email' => 'rahul@example.com', 'phone' => '9876543210'],
            ['name' => 'Priya Singh', 'email' => 'priya@example.com', 'phone' => '9876543211'],
            ['name' => 'Amit Kumar', 'email' => 'amit@example.com', 'phone' => '9876543212'],
            ['name' => 'Neha Patel', 'email' => 'neha@example.com', 'phone' => '9876543213'],
            ['name' => 'Ravi Das', 'email' => 'ravi@example.com', 'phone' => '9876543214'],
        ];

        foreach ($customerData as $custData) {
            $customers[] = Customer::updateOrCreate(
                ['email' => $custData['email']],
                [
                    'name' => $custData['name'],
                    'email' => $custData['email'],
                    'phone' => $custData['phone'],
                    'password' => bcrypt('password123'),
                    'is_active' => true,
                    'token' => bin2hex(random_bytes(32)),
                ]
            );
        }

        echo "Creating Service Providers with capabilities and tasks...\n";
        foreach ($spData as $data) {
            // Create SP User
            $spUser = SPUser::updateOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'mobile1_number' => $data['phone'],
                    'email' => $data['email'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => 'India',
                    'is_online' => $data['is_online'],
                    'can_work_nights' => $data['can_work_nights'],
                    'last_seen_at' => $data['last_seen_at'],
                    'token' => bin2hex(random_bytes(32)),
                    'is_active' => true,
                ]
            );

            // Create Service Provider
            $sp = ServiceProvider::updateOrCreate(
                ['sp_user_id' => $spUser->id],
                array_merge($data['sp_data'], ['sp_user_id' => $spUser->id])
            );

            // Create capabilities for each subcategory
            foreach ($data['subcategories'] as $subcategorySlug) {
                $subcategory = NewSubcategory::where('slug', $subcategorySlug)->first();

                if ($subcategory) {
                    // find the primary category via the pivot relationship
                    $primaryCategory = $subcategory->getPrimaryCategory();

                    SpCapability::updateOrCreate(
                        [
                            'service_provider_id' => $sp->id,
                            'subcategory_id' => $subcategory->id,
                        ],
                        [
                            'service_provider_id' => $sp->id,
                            'category_id' => $primaryCategory?->id,  // ✅ new_categories.id
                            'subcategory_id' => $subcategory->id,
                            'max_pax_capacity' => rand(5, 20),
                            'max_travel_distance_km' => rand(10, 30),
                            'night_shift_available' => $data['can_work_nights'],
                            'is_active' => true,
                        ]
                    );
                }
            }



            // Create cuisine capabilities (for chef SPs)
            foreach ($data['cuisines'] as $cuisineName) {
                $cuisine = ChefCuisine::where('name', $cuisineName)->first();
                if ($cuisine) {
                    SpCuisineCapability::updateOrCreate(
                        [
                            'service_provider_id' => $sp->id,
                            'chef_cuisine_id' => $cuisine->id,
                        ],
                        [
                            'service_provider_id' => $sp->id,
                            'chef_cuisine_id' => $cuisine->id,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Create dietary capabilities
            foreach ($data['dietary_preferences'] as $preferenceName) {
                $preference = DietaryPreference::where('name', $preferenceName)->first();
                if ($preference) {
                    SpDietaryCapability::updateOrCreate(
                        [
                            'service_provider_id' => $sp->id,
                            'dietary_preference_id' => $preference->id,
                        ],
                        [
                            'service_provider_id' => $sp->id,
                            'dietary_preference_id' => $preference->id,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Create addon capabilities
            foreach ($data['addon_flags'] as $flagName) {
                $flag = ChefAddonFlag::where('name', $flagName)->first();
                if ($flag) {
                    SpAddonCapability::updateOrCreate(
                        [
                            'service_provider_id' => $sp->id,
                            'chef_addon_flag_id' => $flag->id,
                        ],
                        [
                            'service_provider_id' => $sp->id,
                            'chef_addon_flag_id' => $flag->id,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Create optional capabilities
            foreach ($data['optional_flags'] as $flagName) {
                $flag = OptionalFlag::where('name', $flagName)->first();
                if ($flag) {
                    SpOptionalCapability::updateOrCreate(
                        [
                            'service_provider_id' => $sp->id,
                            'optional_flag_id' => $flag->id,
                        ],
                        [
                            'service_provider_id' => $sp->id,
                            'optional_flag_id' => $flag->id,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Create multiple tasks for each SP to test task details functionality
            $this->createTasksForSP($sp, $customers, $data);
        }
    }

    /**
     * Create multiple tasks for each SP to test task details functionality
     */
    private function createTasksForSP($sp, $customers, $spData): void
    {
        // Get categories and subcategories for this SP
        $categories = NewCategory::whereIn('slug', ['chef', 'house-help', 'driver'])->get();
        $subcategories = NewSubcategory::whereIn('slug', $spData['subcategories'])->get();

        // Create 3-5 tasks per SP with different statuses
        $taskStatuses = ['completed', 'assigned', 'started', 'rated', 'cancelled'];
        $taskCount = rand(3, 5);

        for ($i = 0; $i < $taskCount; $i++) {
            $customer = $customers[array_rand($customers)];
            $category = $categories->random();
            $subcategory = $subcategories->random();
            $status = $taskStatuses[array_rand($taskStatuses)];

            // Generate task number
            $taskNumber = 'TSK' . date('Ymd') . str_pad($sp->id * 10 + $i, 3, '0', STR_PAD_LEFT);

            // Set dates based on status
            $scheduledAt = now()->subDays(rand(1, 30))->addHours(rand(8, 20));
            $startedAt = $status !== 'assigned' ? $scheduledAt->copy()->addMinutes(rand(5, 30)) : null;
            $completedAt = in_array($status, ['completed', 'rated']) ? $startedAt?->copy()->addHours(rand(1, 4)) : null;

            Task::updateOrCreate(
                ['task_number' => $taskNumber],
                [
                    'task_number' => $taskNumber,
                    'customer_id' => $customer->id,
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                    'service_provider_id' => $sp->id,
                    'pax_count' => rand(1, 6),
                    'requested_hours' => rand(1, 8),
                    'billable_hours' => rand(1, 8),
                    'dates' => [$scheduledAt->format('Y-m-d')],
                    'start_time' => $scheduledAt->format('H:i:s'),
                    'end_time' => $scheduledAt->copy()->addHours(rand(2, 6))->format('H:i:s'),
                    'recurrence_type' => 'one_time',
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'assigned_at' => $status !== 'requested' ? $scheduledAt->copy()->subMinutes(rand(10, 60)) : null,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'cancelled_at' => $status === 'cancelled' ? $scheduledAt->copy()->addMinutes(rand(30, 120)) : null,
                    'total_amount' => rand(200, 1000),
                    'gst_amount' => rand(36, 180),
                    'final_amount' => rand(236, 1180),
                    'special_instructions' => $this->getRandomInstructions(),
                    'cancellation_reason' => $status === 'cancelled' ? $this->getRandomCancellationReason() : null,
                    'cancelled_by' => $status === 'cancelled' ? (rand(0, 1) ? 'customer' : 'sp') : null,
                    'customer_rating' => in_array($status, ['completed', 'rated']) ? rand(3, 5) : null,
                    'customer_feedback' => in_array($status, ['completed', 'rated']) ? $this->getRandomCustomerFeedback() : null,
                    'sp_rating' => in_array($status, ['completed', 'rated']) ? rand(3, 5) : null,
                    'sp_feedback' => in_array($status, ['completed', 'rated']) ? $this->getRandomSPFeedback() : null,
                    'is_active' => true,
                ]
            );
        }
    }

    private function getRandomInstructions(): string
    {
        $instructions = [
            'Please arrive on time',
            'Call before arriving',
            'Use the back entrance',
            'Vegetarian food only',
            'No spicy food',
            'Extra cleaning required',
            'Handle with care',
            'Follow safety protocols',
        ];
        return $instructions[array_rand($instructions)];
    }

    private function getRandomCancellationReason(): string
    {
        $reasons = [
            'Customer not available',
            'Emergency situation',
            'Weather conditions',
            'SP unavailable',
            'Change in requirements',
            'Technical issues',
        ];
        return $reasons[array_rand($reasons)];
    }

    private function getRandomCustomerFeedback(): string
    {
        $feedback = [
            'Excellent service, very professional',
            'Good work, satisfied with the service',
            'Average service, room for improvement',
            'Great job, will book again',
            'Professional and punctual',
            'Quality work, highly recommended',
        ];
        return $feedback[array_rand($feedback)];
    }

    private function getRandomSPFeedback(): string
    {
        $feedback = [
            'Customer was cooperative',
            'Good working environment',
            'Clear instructions provided',
            'Pleasant customer interaction',
            'Well-organized requirements',
            'Smooth task execution',
        ];
        return $feedback[array_rand($feedback)];
    }

    private function createSampleTasks(): void
    {
        $this->command->info('Creating sample tasks...');

        $customer = Customer::first();



        $chefCategory = NewCategory::where('slug', 'chef')->first();
        $lunchSubcategory = NewSubcategory::where('slug', 'lunch')->first();

        $customerAddress = $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->first();

        // if still nothing (just in case), create a fallback:
        if (! $customerAddress) {
            $customerAddress = CustomerAddress::create([
                'customer_id'     => $customer->id,
                'label'           => 'Home',
                'address_line_1'  => '123 Test Street',
                'city'            => 'Bengaluru',
                'state'           => 'Karnataka',
                'postal_code'     => '560001',
                'latitude'        => 12.9716,
                'longitude'       => 77.5946,
                'is_default'      => true,
                'is_active'       => true,
            ]);
        }

        if ($customer && $customerAddress && $chefCategory && $lunchSubcategory) {
            $task = Task::updateOrCreate(
                ['task_number' => 'TSK20241205001'],
                [
                    'task_number' => 'TSK20241205001',
                    'customer_id' => $customer->id,
                    'customer_address_id' =>12,
                    'category_id' => $chefCategory->id,
                    'subcategory_id' => $lunchSubcategory->id,
                    'pax_count' => 4,
                    'requested_hours' => 2,
                    'billable_hours' => 2,
                    'dates' => [now()->addDays(1)->format('Y-m-d')],
                    'start_time' => '12:00:00',
                    'end_time' => '14:00:00',
                    'recurrence_type' => 'one_time',
                    'status' => 'completed',
                    'scheduled_at' => now()->addDays(1)->setTime(12, 0),
                    'completed_at' => now()->subDays(1),
                    'total_amount' => 500.00,
                    'gst_amount' => 90.00,
                    'final_amount' => 590.00,
                    'is_active' => true,
                ]
            );

            // Create price components
            TaskPriceComponent::updateOrCreate(
                ['task_id' => $task->id],
                [
                    'task_id' => $task->id,
                    'base_amount' => 500.00,
                    'night_adjustment' => 0.00,
                    'festive_surge' => 0.00,
                    'weather_surge' => 0.00,
                    'premium_sp_charge' => 0.00,
                    'consultation_fee' => 0.00,
                    'stay_over_fee' => 0.00,
                    'subscription_discount' => 0.00,
                    'total_excl_gst' => 500.00,
                    'gst_amount' => 90.00,
                    'final_amount' => 590.00,
                ]
            );
        }
    }
}
