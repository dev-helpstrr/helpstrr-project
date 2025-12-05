<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\SPUser;
use App\Models\ServiceProvider;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\Service;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\OptionalFlag;
use App\Models\ChefAddonFlag;
use App\Models\Task;
use App\Models\TaskPriceComponent;
use App\Models\SPCapability;
use App\Models\SPCuisineCapability;
use App\Models\SPDietaryCapability;
use App\Models\SPAddonCapability;
use App\Models\SPOptionalCapability;
use App\Models\SpLocationTracking;
use App\Models\TaskBroadcast;
use App\Models\TaskAssignmentLog;
use Illuminate\Support\Facades\Hash;

class ComprehensiveTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating comprehensive test data...');

        // Check if data already exists
        if (Customer::count() > 0) {
            $this->command->info('Test data already exists. Skipping seeder...');
            return;
        }

        // Create test customers
        $this->createCustomers();
        
        // Create service providers
        $this->createServiceProviders();
        
        // Create old category system data
        $this->createOldCategorySystem();
        
        // Create new category system data
        $this->createNewCategorySystem();
        
        // Create chef-specific data
        $this->createChefData();
        
        // Create provider capabilities (38 filtering criteria)
        $this->createProviderCapabilities();
        
        // Create location tracking data
        $this->createLocationTrackingData();
        
        // Create sample tasks/bookings
        $this->createSampleTasks();
        
        // Create task broadcasts for testing auto-assignment
        $this->createTaskBroadcasts();

        $this->command->info('Comprehensive test data created successfully!');
    }

    private function createCustomers(): void
    {
        $this->command->info('Creating test customers...');

        $customers = [
            [
                'name' => 'John Doe',
                'phone' => '9876543210',
                'email' => 'john.doe@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'addresses' => [
                    [
                        'label' => 'home',
                        'address_line_1' => '123 Main Street',
                        'address_line_2' => 'Apartment 4B',
                        'city' => 'Mumbai',
                        'state' => 'Maharashtra',
                        'pincode' => '400001',
                        'latitude' => 19.0760,
                        'longitude' => 72.8777,
                        'is_default' => true,
                    ],
                    [
                        'label' => 'office',
                        'address_line_1' => '456 Business Park',
                        'city' => 'Mumbai',
                        'state' => 'Maharashtra',
                        'pincode' => '400070',
                        'latitude' => 19.1136,
                        'longitude' => 72.8697,
                    ]
                ]
            ],
            [
                'name' => 'Jane Smith',
                'phone' => '9876543211',
                'email' => 'jane.smith@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'addresses' => [
                    [
                        'label' => 'home',
                        'address_line_1' => '789 Garden View',
                        'city' => 'Delhi',
                        'state' => 'Delhi',
                        'pincode' => '110001',
                        'latitude' => 28.6139,
                        'longitude' => 77.2090,
                        'is_default' => true,
                    ]
                ]
            ],
            [
                'name' => 'Raj Patel',
                'phone' => '9876543212',
                'email' => 'raj.patel@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'addresses' => [
                    [
                        'label' => 'home',
                        'address_line_1' => '321 Tech Hub',
                        'city' => 'Bangalore',
                        'state' => 'Karnataka',
                        'pincode' => '560001',
                        'latitude' => 12.9716,
                        'longitude' => 77.5946,
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
                        'label' => $addressData['label']
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
        $this->command->info('Creating test service providers...');

        $spUsers = [
            [
                'first_name' => 'Ramesh',
                'last_name' => 'Kumar',
                'mobile1_number' => '9876543220',
                'email' => 'ramesh.chef@example.com',
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'intrested_role' => 'chef',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400001',
            ],
            [
                'first_name' => 'Suresh',
                'last_name' => 'Singh',
                'mobile1_number' => '9876543221',
                'email' => 'suresh.driver@example.com',
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'intrested_role' => 'driver',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400001',
            ],
            [
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'mobile1_number' => '9876543222',
                'email' => 'priya.help@example.com',
                'token' => bin2hex(random_bytes(32)),
                'is_active' => true,
                'intrested_role' => 'house_help',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400001',
            ]
        ];

        foreach ($spUsers as $spData) {
            $spUser = SPUser::updateOrCreate(
                ['email' => $spData['email']],
                $spData
            );
            
            // Create corresponding ServiceProvider record
            ServiceProvider::updateOrCreate(
                ['sp_user_id' => $spUser->id],
                [
                    'sp_user_id' => $spUser->id,
                    'rating' => rand(40, 50) / 10, // 4.0 to 5.0 rating
                    'total_ratings' => rand(10, 100),
                    'tasks_completed' => rand(5, 50),
                    'is_active' => true,
                    'kyc_verified' => true,
                    'kyc_status' => 'approved',
                ]
            );
        }
    }

    private function createOldCategorySystem(): void
    {
        $this->command->info('Creating old category system data...');

        $categories = [
            [
                'name' => 'Home Services',
                'slug' => 'home-services',
                'description' => 'Professional home services',
                'icon' => 'home',
                'night_multiplier' => 1.5,
                'is_active' => true,
                'sort_order' => 1,
                'subcategories' => [
                    [
                        'name' => 'Cleaning',
                        'slug' => 'cleaning',
                        'description' => 'House cleaning services',
                        'hourly_rate' => 200.00,
                        'min_hours' => 2,
                        'consultation_fee' => 0.00,
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Cooking',
                        'slug' => 'cooking',
                        'description' => 'Professional cooking services',
                        'hourly_rate' => 300.00,
                        'min_hours' => 2,
                        'consultation_fee' => 50.00,
                        'is_active' => true,
                        'sort_order' => 2,
                    ]
                ]
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation',
                'description' => 'Transportation and delivery services',
                'icon' => 'car',
                'night_multiplier' => 2.0,
                'is_active' => true,
                'sort_order' => 2,
                'subcategories' => [
                    [
                        'name' => 'Personal Driver',
                        'slug' => 'personal-driver',
                        'description' => 'Personal driving services',
                        'hourly_rate' => 150.00,
                        'min_hours' => 4,
                        'consultation_fee' => 0.00,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $subcategories = $categoryData['subcategories'];
            unset($categoryData['subcategories']);
            
            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
            
            foreach ($subcategories as $subcategoryData) {
                $category->subcategories()->updateOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    $subcategoryData
                );
            }
        }
    }

    private function createNewCategorySystem(): void
    {
        $this->command->info('Creating new category system data...');

        // Create new categories
        $newCategories = [
            [
                'name' => 'Food & Cooking',
                'slug' => 'food-cooking',
                'description' => 'Professional cooking and food services',
                'icon' => 'utensils',
                'color' => '#FF6B6B',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Home Care',
                'slug' => 'home-care',
                'description' => 'Complete home care solutions',
                'icon' => 'home-heart',
                'color' => '#4ECDC4',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation-new',
                'description' => 'Modern transportation services',
                'icon' => 'car-side',
                'color' => '#45B7D1',
                'is_active' => true,
                'sort_order' => 3,
            ]
        ];

        foreach ($newCategories as $categoryData) {
            NewCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        // Create new subcategories
        $newSubcategories = [
            [
                'name' => 'Personal Chef',
                'slug' => 'personal-chef',
                'description' => 'Professional personal chef services',
                'icon' => 'chef-hat',
                'color' => '#FF8A80',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Event Catering',
                'slug' => 'event-catering',
                'description' => 'Catering for events and parties',
                'icon' => 'party',
                'color' => '#FFB74D',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'House Cleaning',
                'slug' => 'house-cleaning-new',
                'description' => 'Professional house cleaning',
                'icon' => 'spray-bottle',
                'color' => '#81C784',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Personal Driver',
                'slug' => 'personal-driver-new',
                'description' => 'Professional driving services',
                'icon' => 'steering-wheel',
                'color' => '#64B5F6',
                'is_active' => true,
                'sort_order' => 4,
            ]
        ];

        foreach ($newSubcategories as $subcategoryData) {
            NewSubcategory::updateOrCreate(
                ['slug' => $subcategoryData['slug']],
                $subcategoryData
            );
        }

        // Create services
        $services = [
            [
                'name' => 'Daily Meal Preparation',
                'slug' => 'daily-meal-prep',
                'description' => 'Daily meal preparation service',
                'short_description' => 'Fresh meals prepared daily',
                'icon' => 'plate-utensils',
                'base_price' => 500.00,
                'hourly_rate' => 200.00,
                'min_hours' => 2,
                'max_hours' => 8,
                'pax_required' => true,
                'min_pax' => 1,
                'max_pax' => 10,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Party Catering',
                'slug' => 'party-catering',
                'description' => 'Complete party catering service',
                'short_description' => 'Full service party catering',
                'icon' => 'birthday-cake',
                'base_price' => 2000.00,
                'hourly_rate' => 500.00,
                'min_hours' => 4,
                'max_hours' => 12,
                'pax_required' => true,
                'min_pax' => 10,
                'max_pax' => 100,
                'is_event_service' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Deep House Cleaning',
                'slug' => 'deep-cleaning',
                'description' => 'Comprehensive deep cleaning service',
                'short_description' => 'Complete deep cleaning',
                'icon' => 'sparkles',
                'base_price' => 800.00,
                'hourly_rate' => 300.00,
                'min_hours' => 3,
                'max_hours' => 8,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Personal Chauffeur',
                'slug' => 'personal-chauffeur',
                'description' => 'Professional chauffeur service',
                'short_description' => 'Professional driving service',
                'icon' => 'car-front',
                'base_price' => 300.00,
                'hourly_rate' => 150.00,
                'min_hours' => 2,
                'max_hours' => 12,
                'is_active' => true,
                'sort_order' => 4,
            ]
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }

        // Link categories, subcategories, and services
        $foodCategory = NewCategory::where('slug', 'food-cooking')->first();
        $homeCategory = NewCategory::where('slug', 'home-care')->first();
        $transportCategory = NewCategory::where('slug', 'transportation-new')->first();

        $chefSubcategory = NewSubcategory::where('slug', 'personal-chef')->first();
        $cateringSubcategory = NewSubcategory::where('slug', 'event-catering')->first();
        $cleaningSubcategory = NewSubcategory::where('slug', 'house-cleaning-new')->first();
        $driverSubcategory = NewSubcategory::where('slug', 'personal-driver-new')->first();

        // Link categories to subcategories (using sync to avoid duplicates)
        if ($foodCategory && $chefSubcategory && $cateringSubcategory) {
            $foodCategory->subcategories()->syncWithoutDetaching([
                $chefSubcategory->id => ['is_primary' => true, 'sort_order' => 1],
                $cateringSubcategory->id => ['is_primary' => true, 'sort_order' => 2]
            ]);
        }
        
        if ($homeCategory && $cleaningSubcategory) {
            $homeCategory->subcategories()->syncWithoutDetaching([
                $cleaningSubcategory->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }
        
        if ($transportCategory && $driverSubcategory) {
            $transportCategory->subcategories()->syncWithoutDetaching([
                $driverSubcategory->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }

        // Link subcategories to services
        $dailyMealService = Service::where('slug', 'daily-meal-prep')->first();
        $partyCateringService = Service::where('slug', 'party-catering')->first();
        $deepCleaningService = Service::where('slug', 'deep-cleaning')->first();
        $chauffeurService = Service::where('slug', 'personal-chauffeur')->first();

        if ($chefSubcategory && $dailyMealService) {
            $chefSubcategory->services()->syncWithoutDetaching([
                $dailyMealService->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }
        
        if ($cateringSubcategory && $partyCateringService) {
            $cateringSubcategory->services()->syncWithoutDetaching([
                $partyCateringService->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }
        
        if ($cleaningSubcategory && $deepCleaningService) {
            $cleaningSubcategory->services()->syncWithoutDetaching([
                $deepCleaningService->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }
        
        if ($driverSubcategory && $chauffeurService) {
            $driverSubcategory->services()->syncWithoutDetaching([
                $chauffeurService->id => ['is_primary' => true, 'sort_order' => 1]
            ]);
        }
    }

    private function createChefData(): void
    {
        $this->command->info('Creating chef-specific data...');

        // Create cuisines
        $cuisines = [
            ['name' => 'North Indian', 'slug' => 'north-indian', 'is_active' => true],
            ['name' => 'South Indian', 'slug' => 'south-indian', 'is_active' => true],
            ['name' => 'Chinese', 'slug' => 'chinese', 'is_active' => true],
            ['name' => 'Continental', 'slug' => 'continental', 'is_active' => true],
            ['name' => 'Italian', 'slug' => 'italian', 'is_active' => true],
            ['name' => 'Mexican', 'slug' => 'mexican', 'is_active' => true],
        ];

        foreach ($cuisines as $cuisine) {
            ChefCuisine::updateOrCreate(
                ['slug' => $cuisine['slug']],
                $cuisine
            );
        }

        // Create dietary preferences
        $dietaryPreferences = [
            ['name' => 'Vegetarian', 'slug' => 'vegetarian', 'is_active' => true],
            ['name' => 'Vegan', 'slug' => 'vegan', 'is_active' => true],
            ['name' => 'Non-Vegetarian', 'slug' => 'non-vegetarian', 'is_active' => true],
            ['name' => 'Jain', 'slug' => 'jain', 'is_active' => true],
            ['name' => 'Gluten-Free', 'slug' => 'gluten-free', 'is_active' => true],
        ];

        foreach ($dietaryPreferences as $preference) {
            DietaryPreference::updateOrCreate(
                ['slug' => $preference['slug']],
                $preference
            );
        }

        // Create optional flags
        $optionalFlags = [
            ['name' => 'Organic Ingredients', 'slug' => 'organic', 'is_active' => true],
            ['name' => 'Low Salt', 'slug' => 'low-salt', 'is_active' => true],
            ['name' => 'Sugar Free', 'slug' => 'sugar-free', 'is_active' => true],
            ['name' => 'Spice Level - Mild', 'slug' => 'mild-spice', 'is_active' => true],
            ['name' => 'Spice Level - Medium', 'slug' => 'medium-spice', 'is_active' => true],
            ['name' => 'Spice Level - Hot', 'slug' => 'hot-spice', 'is_active' => true],
        ];

        foreach ($optionalFlags as $flag) {
            OptionalFlag::updateOrCreate(
                ['slug' => $flag['slug']],
                $flag
            );
        }

        // Create chef addon flags
        $addonFlags = [
            ['name' => 'Grocery Shopping', 'slug' => 'grocery-shopping', 'description' => 'Shopping for groceries', 'is_active' => true],
            ['name' => 'Kitchen Cleaning', 'slug' => 'kitchen-cleaning', 'description' => 'Cleaning kitchen after cooking', 'is_active' => true],
            ['name' => 'Meal Planning', 'slug' => 'meal-planning', 'description' => 'Planning meals for the week', 'is_active' => true],
            ['name' => 'Recipe Customization', 'slug' => 'recipe-custom', 'description' => 'Customizing recipes as per preference', 'is_active' => true],
        ];

        foreach ($addonFlags as $flag) {
            ChefAddonFlag::updateOrCreate(
                ['slug' => $flag['slug']],
                $flag
            );
        }
    }

    private function createSampleTasks(): void
    {
        $this->command->info('Creating sample tasks/bookings...');

        $customers = Customer::all();
        $categories = Category::all();
        $services = Service::all();

        if ($customers->isEmpty() || $categories->isEmpty() || $services->isEmpty()) {
            $this->command->warn('Skipping task creation - missing required data');
            return;
        }

        $tasks = [
            [
                'customer_id' => $customers->first()->id,
                'customer_address_id' => $customers->first()->addresses->first()->id,
                'category_id' => $categories->first()->id,
                'subcategory_id' => $categories->first()->subcategories->first()->id,
                'service_id' => $services->first()->id,
                'pax_count' => 4,
                'requested_hours' => 3,
                'billable_hours' => 3,
                'dates' => [now()->addDays(1)->format('Y-m-d')],
                'start_time' => '10:00',
                'end_time' => '13:00',
                'recurrence_type' => 'one_time',
                'status' => Task::STATUS_REQUESTED,
                'scheduled_at' => now()->addDays(1)->setTime(10, 0),
                'special_instructions' => 'Please prepare North Indian vegetarian meals',
                'total_amount' => 1200.00,
                'gst_amount' => 216.00,
                'final_amount' => 1416.00,
                'is_active' => true,
            ],
            [
                'customer_id' => $customers->skip(1)->first()->id,
                'customer_address_id' => $customers->skip(1)->first()->addresses->first()->id,
                'category_id' => $categories->first()->id,
                'subcategory_id' => $categories->first()->subcategories->first()->id,
                'service_id' => $services->first()->id,
                'pax_count' => 2,
                'requested_hours' => 4,
                'billable_hours' => 4,
                'dates' => [now()->addDays(2)->format('Y-m-d')],
                'start_time' => '18:00',
                'end_time' => '22:00',
                'recurrence_type' => 'one_time',
                'status' => Task::STATUS_COMPLETED,
                'scheduled_at' => now()->addDays(2)->setTime(18, 0),
                'special_instructions' => 'Continental dinner for 2',
                'total_amount' => 1600.00,
                'gst_amount' => 288.00,
                'final_amount' => 1888.00,
                'customer_rating' => 5,
                'customer_feedback' => 'Excellent service! Food was delicious.',
                'is_active' => true,
            ]
        ];

        foreach ($tasks as $taskData) {
            $task = Task::create($taskData);
            
            // Create price components
            TaskPriceComponent::create([
                'task_id' => $task->id,
                'base_amount' => $taskData['total_amount'] * 0.6,
                'hourly_rate' => 200.00,
                'billable_hours' => $taskData['billable_hours'],
                'total_excl_gst' => $taskData['total_amount'],
                'gst_percentage' => 18.0,
                'gst_amount' => $taskData['gst_amount'],
                'total_incl_gst' => $taskData['final_amount'],
            ]);
        }
    }

    private function createProviderCapabilities(): void
    {
        $this->command->info('Creating provider capabilities for 38 filtering criteria...');

        $serviceProviders = ServiceProvider::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $cuisines = ChefCuisine::all();
        $dietaryPreferences = DietaryPreference::all();
        $optionalFlags = OptionalFlag::all();
        $addonFlags = ChefAddonFlag::all();

        foreach ($serviceProviders as $sp) {
            // Create basic capabilities for each category/subcategory
            foreach ($categories as $category) {
                foreach ($category->subcategories as $subcategory) {
                    SPCapability::updateOrCreate([
                        'service_provider_id' => $sp->id,
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                    ], [
                        'is_active' => true,
                        'hourly_rate' => rand(150, 500),
                        'min_hours' => rand(2, 4),
                        'max_hours' => rand(8, 12),
                        'max_pax_capacity' => rand(5, 20),
                        'max_travel_distance_km' => rand(10, 50),
                        'night_shift_available' => rand(0, 1),
                        'weekend_available' => rand(0, 1),
                        'emergency_available' => rand(0, 1),
                        'advance_booking_days' => rand(1, 30),
                        'cancellation_hours' => rand(2, 24),
                        'experience_years' => rand(1, 15),
                        'certification_level' => ['basic', 'intermediate', 'advanced', 'expert'][rand(0, 3)],
                        'equipment_provided' => rand(0, 1),
                        'materials_provided' => rand(0, 1),
                        'insurance_covered' => rand(0, 1),
                        'background_verified' => true,
                        'language_skills' => json_encode(['English', 'Hindi']),
                        'special_skills' => json_encode(['Quick Service', 'Quality Focus']),
                        'work_environment_preferences' => json_encode(['Indoor', 'Outdoor']),
                        'customer_interaction_level' => ['minimal', 'moderate', 'high'][rand(0, 2)],
                        'physical_requirements_met' => true,
                        'availability_schedule' => json_encode([
                            'monday' => ['09:00-18:00'],
                            'tuesday' => ['09:00-18:00'],
                            'wednesday' => ['09:00-18:00'],
                            'thursday' => ['09:00-18:00'],
                            'friday' => ['09:00-18:00'],
                            'saturday' => ['10:00-16:00'],
                            'sunday' => ['10:00-16:00'],
                        ]),
                        'seasonal_availability' => json_encode(['all_year']),
                        'location_flexibility' => rand(0, 1),
                        'team_work_capability' => rand(0, 1),
                        'technology_comfort_level' => ['basic', 'intermediate', 'advanced'][rand(0, 2)],
                        'customer_rating_threshold' => 4.0,
                        'service_guarantee_offered' => rand(0, 1),
                        'eco_friendly_practices' => rand(0, 1),
                        'cultural_sensitivity_training' => rand(0, 1),
                        'emergency_contact_available' => true,
                        'real_time_tracking_enabled' => rand(0, 1),
                        'quality_assurance_certified' => rand(0, 1),
                        'continuous_improvement_participation' => rand(0, 1),
                    ]);
                }
            }

            // Create cuisine capabilities for chef providers
            if ($sp->spUser->intrested_role === 'chef') {
                foreach ($cuisines as $cuisine) {
                    SPCuisineCapability::updateOrCreate([
                        'service_provider_id' => $sp->id,
                        'chef_cuisine_id' => $cuisine->id,
                    ], [
                        'is_active' => rand(0, 1),
                        'proficiency_level' => ['beginner', 'intermediate', 'expert'][rand(0, 2)],
                        'years_experience' => rand(1, 10),
                    ]);
                }

                // Create dietary capabilities
                foreach ($dietaryPreferences as $dietary) {
                    SPDietaryCapability::updateOrCreate([
                        'service_provider_id' => $sp->id,
                        'dietary_preference_id' => $dietary->id,
                    ], [
                        'is_active' => rand(0, 1),
                        'specialization_level' => ['basic', 'intermediate', 'expert'][rand(0, 2)],
                    ]);
                }

                // Create addon capabilities
                foreach ($addonFlags as $addon) {
                    SPAddonCapability::updateOrCreate([
                        'service_provider_id' => $sp->id,
                        'chef_addon_flag_id' => $addon->id,
                    ], [
                        'is_active' => rand(0, 1),
                        'additional_charge' => rand(0, 200),
                    ]);
                }
            }

            // Create optional capabilities
            foreach ($optionalFlags as $flag) {
                SPOptionalCapability::updateOrCreate([
                    'service_provider_id' => $sp->id,
                    'optional_flag_id' => $flag->id,
                ], [
                    'is_active' => rand(0, 1),
                    'proficiency_level' => ['basic', 'intermediate', 'advanced'][rand(0, 2)],
                ]);
            }

            // Update provider with comprehensive metrics
            $sp->update([
                'rating' => rand(35, 50) / 10, // 3.5 to 5.0
                'total_ratings' => rand(10, 200),
                'tasks_completed' => rand(5, 150),
                'acceptance_rate' => rand(70, 100),
                'punctuality_score' => rand(70, 100),
                'behaviour_score' => rand(75, 100),
                'cancellation_score' => rand(0, 20),
                'complaint_score' => rand(0, 15),
                'rejection_frequency' => rand(0, 30),
                'is_gold_level' => rand(0, 1),
                'last_assigned_at' => now()->subDays(rand(0, 30)),
                'cooldown_until' => null, // No cooldown for test data
            ]);
        }
    }

    private function createLocationTrackingData(): void
    {
        $this->command->info('Creating location tracking data...');

        $serviceProviders = ServiceProvider::all();
        $mumbaiCoordinates = [
            ['lat' => 19.0760, 'lng' => 72.8777], // Mumbai Central
            ['lat' => 19.0896, 'lng' => 72.8656], // Bandra
            ['lat' => 19.1136, 'lng' => 72.8697], // Andheri
            ['lat' => 19.0330, 'lng' => 72.8570], // Worli
            ['lat' => 19.0728, 'lng' => 72.8826], // Fort
        ];

        foreach ($serviceProviders as $sp) {
            $coordinates = $mumbaiCoordinates[array_rand($mumbaiCoordinates)];
            
            // Create current location
            SpLocationTracking::create([
                'service_provider_id' => $sp->id,
                'latitude' => $coordinates['lat'] + (rand(-100, 100) / 10000), // Add small variation
                'longitude' => $coordinates['lng'] + (rand(-100, 100) / 10000),
                'accuracy' => rand(5, 20),
                'speed' => rand(0, 50),
                'heading' => rand(0, 360),
                'altitude' => rand(10, 100),
                'is_active' => true,
                'battery_level' => rand(20, 100),
                'network_type' => ['4G', '5G', 'WiFi'][rand(0, 2)],
                'app_version' => '1.0.0',
                'device_info' => json_encode([
                    'model' => 'Test Device',
                    'os' => 'Android 12',
                    'app_version' => '1.0.0'
                ]),
            ]);

            // Create historical location data
            for ($i = 1; $i <= 10; $i++) {
                SpLocationTracking::create([
                    'service_provider_id' => $sp->id,
                    'latitude' => $coordinates['lat'] + (rand(-200, 200) / 10000),
                    'longitude' => $coordinates['lng'] + (rand(-200, 200) / 10000),
                    'accuracy' => rand(5, 30),
                    'speed' => rand(0, 60),
                    'heading' => rand(0, 360),
                    'altitude' => rand(10, 100),
                    'is_active' => false,
                    'battery_level' => rand(20, 100),
                    'network_type' => ['4G', '5G', 'WiFi'][rand(0, 2)],
                    'app_version' => '1.0.0',
                    'created_at' => now()->subHours($i),
                    'updated_at' => now()->subHours($i),
                ]);
            }

            // Update SP user with current location
            $sp->spUser->update([
                'latitude' => $coordinates['lat'],
                'longitude' => $coordinates['lng'],
                'is_online' => rand(0, 1),
                'last_seen_at' => now()->subMinutes(rand(0, 120)),
            ]);
        }
    }

    private function createTaskBroadcasts(): void
    {
        $this->command->info('Creating task broadcasts for auto-assignment testing...');

        $tasks = Task::where('status', Task::STATUS_REQUESTED)->get();
        $serviceProviders = ServiceProvider::active()->get();

        foreach ($tasks as $task) {
            // Create some sample broadcasts
            $selectedProviders = $serviceProviders->random(min(5, $serviceProviders->count()));
            
            foreach ($selectedProviders as $index => $sp) {
                $broadcast = TaskBroadcast::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $sp->id,
                    'broadcast_round' => $index < 2 ? 'A' : 'B',
                    'distance_km' => rand(1, 15),
                    'sp_rating' => $sp->rating,
                    'sp_rank_in_round' => $index + 1,
                    'sent_at' => now()->subMinutes(rand(5, 60)),
                    'expires_at' => now()->addMinutes(rand(30, 120)),
                    'timeout_seconds' => 60,
                    'response' => ['pending', 'accepted', 'rejected', 'timeout'][rand(0, 3)],
                    'responded_at' => rand(0, 1) ? now()->subMinutes(rand(1, 30)) : null,
                    'response_time_seconds' => rand(10, 300),
                    'rejection_reason' => rand(0, 1) ? 'Not available at that time' : null,
                ]);

                // Create corresponding assignment log
                TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $sp->id,
                    'action_type' => $this->getActionTypeFromResponse($broadcast->response),
                    'action_timestamp' => $broadcast->responded_at ?? $broadcast->sent_at,
                    'response_time_seconds' => $broadcast->response_time_seconds,
                    'distance_km' => $broadcast->distance_km,
                    'priority_score' => $this->calculateMockPriorityScore($sp, $broadcast->distance_km, $broadcast->response_time_seconds),
                    'conflict_resolution_applied' => rand(0, 10) === 0, // 10% chance
                    'assignment_round' => $index + 1,
                    'broadcast_id' => $broadcast->id,
                    'rejection_reason' => $broadcast->rejection_reason,
                    'auto_assigned' => false,
                    'metadata' => [
                        'provider_rating' => $sp->rating,
                        'provider_experience' => $sp->experience_years,
                        'mock_data' => true,
                    ],
                ]);
            }
        }

        $this->command->info('✅ Task assignment logs created successfully');
    }

    /**
     * Get action type from broadcast response
     */
    private function getActionTypeFromResponse(string $response): string
    {
        return match($response) {
            'accepted' => TaskAssignmentLog::ACTION_ACCEPTED,
            'rejected' => TaskAssignmentLog::ACTION_REJECTED,
            'timeout' => TaskAssignmentLog::ACTION_TIMEOUT,
            default => TaskAssignmentLog::ACTION_BROADCAST_SENT,
        };
    }

    /**
     * Calculate mock priority score for testing
     */
    private function calculateMockPriorityScore(ServiceProvider $sp, float $distance, int $responseTime): float
    {
        $score = 0;
        
        // Distance factor (closer = higher score)
        $score += max(0, 100 - ($distance * 2));
        
        // Response time factor (faster = higher score)
        $score += max(0, 100 - ($responseTime / 10));
        
        // Provider rating factor
        $score += $sp->rating * 10;
        
        // Provider metrics factor
        $score += ($sp->acceptance_rate ?? 80) * 0.5;
        $score += ($sp->punctuality_score ?? 85) * 0.3;
        
        return round($score, 2);
    }
}