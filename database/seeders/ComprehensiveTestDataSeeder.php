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
        
        // Create sample tasks/bookings
        $this->createSampleTasks();

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
}