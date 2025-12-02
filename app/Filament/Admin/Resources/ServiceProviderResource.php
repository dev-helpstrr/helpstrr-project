<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SP\Auth\SortkarSPUser;
use App\Models\SortkarJobRole;
use Filament\Tables\Columns\BadgeColumn;
use App\Filament\Admin\Resources\ServiceProviderResource\Pages;

class ServiceProviderResource extends Resource
{
    protected static ?string $model = SortkarSPUser::class;
    protected static ?string $role_model = SortkarJobRole::class;

    // 🧭 Sidebar navigation settings
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Service Providers';
    protected static ?string $pluralModelLabel = 'Service Providers';
    protected static ?string $modelLabel = 'Service Provider';
    protected static ?string $navigationGroup = 'Admin Management';
    protected static ?int $navigationSort = 1;

    // 🏷️ Optional: Change breadcrumb name
    protected static ?string $breadcrumb = 'Providers List';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Service Provider Details')
                ->tabs([
                    // Basic Information Tab
                    Forms\Components\Tabs\Tab::make('Basic Information')
                        ->schema([
                            Forms\Components\Section::make('Personal Details')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('first_name')
                                                ->label('First Name')
                                                ->required()
                                                ->maxLength(50),
                                            Forms\Components\TextInput::make('last_name')
                                                ->label('Last Name')
                                                ->required()
                                                ->maxLength(50),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('mobile1_number')
                                                ->label('Primary Mobile')
                                                ->required()
                                                ->tel()
                                                ->maxLength(15),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('alternate_mobile')
                                                ->label('Alternate Mobile')
                                                ->tel()
                                                ->maxLength(15),
                                            Forms\Components\TextInput::make('whatsapp')
                                                ->label('WhatsApp Number')
                                                ->tel()
                                                ->maxLength(15),
                                        ]),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\DatePicker::make('dob')
                                                ->label('Date of Birth')
                                                ->maxDate(now()->subYears(18)),
                                            Forms\Components\TextInput::make('age')
                                                ->label('Age')
                                                ->numeric()
                                                ->minValue(18)
                                                ->maxValue(70),
                                            Forms\Components\Select::make('gender')
                                                ->label('Gender')
                                                ->options([
                                                    'male' => 'Male',
                                                    'female' => 'Female',
                                                    'other' => 'Other',
                                                ])
                                                ->required(),
                                        ]),
                                    Forms\Components\TagsInput::make('languages_known')
                                        ->label('Languages Known')
                                        ->placeholder('Add languages...')
                                        ->suggestions(['Hindi', 'English', 'Bengali', 'Tamil', 'Telugu', 'Marathi', 'Gujarati', 'Kannada', 'Malayalam', 'Punjabi']),
                                ]),

                            Forms\Components\Section::make('Address & Location')
                                ->schema([
                                    Forms\Components\Textarea::make('address')
                                        ->label('Full Address')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('city')
                                                ->label('City')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('state')
                                                ->label('State')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('pincode')
                                                ->label('PIN Code')
                                                ->numeric()
                                                ->length(6),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('latitude')
                                                ->label('Latitude')
                                                ->numeric()
                                                ->step(0.000001),
                                            Forms\Components\TextInput::make('longitude')
                                                ->label('Longitude')
                                                ->numeric()
                                                ->step(0.000001),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Service Categories')
                                ->schema([
                                    Forms\Components\CheckboxList::make('service_categories')
                                        ->label('Service Categories')
                                        ->options([
                                            'house_help' => 'House Help',
                                            'driver' => 'Driver',
                                            'chef' => 'Chef',
                                        ])
                                        ->columns(3)
                                        ->required()
                                        ->live(),
                                ]),
                        ]),

                    // Experience & Skills Tab
                    Forms\Components\Tabs\Tab::make('Experience & Skills')
                        ->schema([
                            Forms\Components\Section::make('General Experience')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('experience_years')
                                                ->label('Years of Experience')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(50),
                                            Forms\Components\TextInput::make('prior_experience')
                                                ->label('Prior Experience Details')
                                                ->maxLength(200),
                                        ]),
                                    Forms\Components\Textarea::make('bio')
                                        ->label('Bio/Description')
                                        ->rows(4)
                                        ->maxLength(1000),
                                    Forms\Components\TagsInput::make('certifications')
                                        ->label('Certifications')
                                        ->placeholder('Add certifications...'),
                                ]),

                            Forms\Components\Section::make('Work Preferences')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('expected_hourly_rate')
                                                ->label('Expected Hourly Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('expected_daily_rate')
                                                ->label('Expected Daily Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('max_daily_working_hours')
                                                ->label('Max Daily Working Hours')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(16),
                                            Forms\Components\TextInput::make('max_travel_distance')
                                                ->label('Max Travel Distance (km)')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(100),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('can_work_weekends')
                                                ->label('Can Work Weekends')
                                                ->default(true),
                                            Forms\Components\Toggle::make('can_work_nights')
                                                ->label('Can Work Nights')
                                                ->default(false),
                                        ]),
                                    Forms\Components\TagsInput::make('preferred_working_areas')
                                        ->label('Preferred Working Areas')
                                        ->placeholder('Add areas...'),
                                    Forms\Components\Textarea::make('special_conditions')
                                        ->label('Special Conditions/Requirements')
                                        ->rows(3)
                                        ->maxLength(500),
                                ]),
                        ]),

                    // Driver Details Tab
                    Forms\Components\Tabs\Tab::make('Driver Details')
                        ->schema([
                            Forms\Components\Section::make('License Information')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('driverDetail.license_number')
                                                ->label('License Number')
                                                ->maxLength(20),
                                            Forms\Components\DatePicker::make('driverDetail.license_expiry_date')
                                                ->label('License Expiry Date')
                                                ->minDate(now()),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\FileUpload::make('driverDetail.license_front_image')
                                                ->label('License Front Image')
                                                ->image()
                                                ->maxSize(2048),
                                            Forms\Components\FileUpload::make('driverDetail.license_back_image')
                                                ->label('License Back Image')
                                                ->image()
                                                ->maxSize(2048),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Driving Experience')
                                ->schema([
                                    Forms\Components\TextInput::make('driverDetail.years_of_experience')
                                        ->label('Years of Driving Experience')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(50),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\CheckboxList::make('driverDetail.transmission_types')
                                                ->label('Transmission Types')
                                                ->options([
                                                    'manual' => 'Manual',
                                                    'automatic' => 'Automatic',
                                                ])
                                                ->columns(2),
                                            Forms\Components\CheckboxList::make('driverDetail.vehicle_segments')
                                                ->label('Vehicle Segments')
                                                ->options([
                                                    'hatchback' => 'Hatchback',
                                                    'sedan' => 'Sedan',
                                                    'suv' => 'SUV',
                                                    'mpv' => 'MPV',
                                                ])
                                                ->columns(2),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Fieldset::make('Experience Types')
                                                ->schema([
                                                    Forms\Components\Toggle::make('driverDetail.city_driving_experience')
                                                        ->label('City Driving'),
                                                    Forms\Components\Toggle::make('driverDetail.highway_driving_experience')
                                                        ->label('Highway Driving'),
                                                    Forms\Components\Toggle::make('driverDetail.night_driving_experience')
                                                        ->label('Night Driving'),
                                                    Forms\Components\Toggle::make('driverDetail.traffic_heavy_experience')
                                                        ->label('Heavy Traffic'),
                                                ]),
                                            Forms\Components\Fieldset::make('Service Capabilities')
                                                ->schema([
                                                    Forms\Components\Toggle::make('driverDetail.ready_for_outstation')
                                                        ->label('Outstation Trips'),
                                                    Forms\Components\Toggle::make('driverDetail.ready_for_airport_pickup')
                                                        ->label('Airport Pickup/Drop'),
                                                    Forms\Components\Toggle::make('driverDetail.ready_for_office_commute')
                                                        ->label('Office Commute'),
                                                    Forms\Components\Toggle::make('driverDetail.comfortable_long_hours')
                                                        ->label('Long Hours Comfortable'),
                                                ]),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Driver Pricing')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('driverDetail.expected_hourly_rate')
                                                ->label('Hourly Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('driverDetail.expected_daily_rate')
                                                ->label('Daily Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('driverDetail.outstation_per_km_rate')
                                                ->label('Outstation Per KM (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                        ]),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get): bool => in_array('driver', $get('service_categories') ?? [])),

                    // Chef Details Tab
                    Forms\Components\Tabs\Tab::make('Chef Details')
                        ->schema([
                            Forms\Components\Section::make('Cooking Experience')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('chefDetail.years_of_cooking_experience')
                                                ->label('Years of Cooking Experience')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(50),
                                            Forms\Components\Select::make('chefDetail.chef_type')
                                                ->label('Chef Type')
                                                ->options([
                                                    'home_cook' => 'Home Cook',
                                                    'event_cook' => 'Event Cook',
                                                    'specialist_cook' => 'Specialist Cook',
                                                ]),
                                        ]),
                                    Forms\Components\CheckboxList::make('chefDetail.cuisine_specialties')
                                        ->label('Cuisine Specialties')
                                        ->options([
                                            'bengali' => 'Bengali',
                                            'north_indian' => 'North Indian',
                                            'south_indian' => 'South Indian',
                                            'chinese' => 'Chinese',
                                            'continental' => 'Continental',
                                            'mughlai' => 'Mughlai',
                                            'snacks' => 'Snacks/Party Starters',
                                            'biryani' => 'Biryani Specialist',
                                        ])
                                        ->columns(3),
                                ]),

                            Forms\Components\Section::make('Service Capabilities')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Fieldset::make('Meal Types')
                                                ->schema([
                                                    Forms\Components\Toggle::make('chefDetail.can_cook_breakfast')
                                                        ->label('Breakfast'),
                                                    Forms\Components\Toggle::make('chefDetail.can_cook_lunch')
                                                        ->label('Lunch'),
                                                    Forms\Components\Toggle::make('chefDetail.can_cook_dinner')
                                                        ->label('Dinner'),
                                                    Forms\Components\Toggle::make('chefDetail.can_cook_full_day')
                                                        ->label('Full Day'),
                                                    Forms\Components\Toggle::make('chefDetail.can_cook_special_occasions')
                                                        ->label('Special Occasions'),
                                                ]),
                                            Forms\Components\Fieldset::make('Additional Services')
                                                ->schema([
                                                    Forms\Components\Toggle::make('chefDetail.can_bring_utensils')
                                                        ->label('Can Bring Utensils'),
                                                    Forms\Components\Toggle::make('chefDetail.can_bring_raw_materials')
                                                        ->label('Can Bring Raw Materials'),
                                                ]),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('chefDetail.dietary_preference')
                                                ->label('Dietary Preference')
                                                ->options([
                                                    'veg_only' => 'Vegetarian Only',
                                                    'veg_nonveg' => 'Veg + Non-Veg',
                                                    'jain_food' => 'Jain Food',
                                                    'all' => 'All Types',
                                                ])
                                                ->default('all'),
                                            Forms\Components\CheckboxList::make('chefDetail.pax_capacity')
                                                ->label('Pax Capacity')
                                                ->options([
                                                    '1-4' => '1-4 people',
                                                    '5-10' => '5-10 people',
                                                    '10-25' => '10-25 people',
                                                    '25-50' => '25-50 people',
                                                    '50+' => '50+ people (Events)',
                                                ])
                                                ->columns(2),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Chef Pricing')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('chefDetail.price_per_meal')
                                                ->label('Price Per Meal (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('chefDetail.price_per_day')
                                                ->label('Price Per Day (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('chefDetail.price_per_event')
                                                ->label('Price Per Event (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Portfolio')
                                ->schema([
                                    Forms\Components\FileUpload::make('chefDetail.food_portfolio_images')
                                        ->label('Food Portfolio Images')
                                        ->image()
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->maxSize(2048),
                                    Forms\Components\Textarea::make('chefDetail.speciality_description')
                                        ->label('Speciality Description')
                                        ->rows(4)
                                        ->maxLength(1000),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get): bool => in_array('chef', $get('service_categories') ?? [])),

                    // House Help Details Tab
                    Forms\Components\Tabs\Tab::make('House Help Details')
                        ->schema([
                            Forms\Components\Section::make('Skills & Capabilities')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Fieldset::make('Basic Cleaning Skills')
                                                ->schema([
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_clean_utensils')
                                                        ->label('Utensils Cleaning'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_sweep_mop')
                                                        ->label('Sweeping & Mopping'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_dust')
                                                        ->label('Dusting'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_clean_bathroom')
                                                        ->label('Bathroom Cleaning'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_clean_kitchen')
                                                        ->label('Kitchen Cleaning'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_deep_clean')
                                                        ->label('Deep Cleaning'),
                                                ]),
                                            Forms\Components\Fieldset::make('Additional Skills')
                                                ->schema([
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_wash_clothes')
                                                        ->label('Clothes Washing'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_iron_clothes')
                                                        ->label('Clothes Ironing'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_cook_basic_meals')
                                                        ->label('Basic Cooking'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_grocery_shopping')
                                                        ->label('Grocery Shopping'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_gardening')
                                                        ->label('Gardening'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_car_cleaning')
                                                        ->label('Car Cleaning'),
                                                ]),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Fieldset::make('Special Care')
                                                ->schema([
                                                    Forms\Components\Toggle::make('houseHelpDetail.comfortable_with_pets')
                                                        ->label('Comfortable with Pets'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_assist_elderly')
                                                        ->label('Elderly Assistance'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.can_handle_babies')
                                                        ->label('Baby Handling'),
                                                ]),
                                            Forms\Components\Fieldset::make('Equipment & Supplies')
                                                ->schema([
                                                    Forms\Components\Toggle::make('houseHelpDetail.has_own_cleaning_supplies')
                                                        ->label('Has Own Cleaning Supplies'),
                                                    Forms\Components\Toggle::make('houseHelpDetail.has_own_equipment')
                                                        ->label('Has Own Equipment'),
                                                ]),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Work Preferences')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('houseHelpDetail.min_hours_per_day')
                                                ->label('Min Hours Per Day')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(12),
                                            Forms\Components\TextInput::make('houseHelpDetail.max_hours_per_day')
                                                ->label('Max Hours Per Day')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(16),
                                            Forms\Components\TextInput::make('houseHelpDetail.max_hours_per_task')
                                                ->label('Max Hours Per Task')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(8),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('houseHelpDetail.available_weekends')
                                                ->label('Available Weekends')
                                                ->default(true),
                                            Forms\Components\Toggle::make('houseHelpDetail.available_festivals')
                                                ->label('Available Festivals')
                                                ->default(false),
                                        ]),
                                ]),

                            Forms\Components\Section::make('House Help Pricing')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('houseHelpDetail.hourly_rate')
                                                ->label('Hourly Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('houseHelpDetail.daily_rate')
                                                ->label('Daily Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                            Forms\Components\TextInput::make('houseHelpDetail.monthly_rate')
                                                ->label('Monthly Rate (₹)')
                                                ->numeric()
                                                ->prefix('₹')
                                                ->step(0.01),
                                        ]),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get): bool => in_array('house_help', $get('service_categories') ?? [])),

                    // Status & Verification Tab
                    Forms\Components\Tabs\Tab::make('Status & Verification')
                        ->schema([
                            Forms\Components\Section::make('Account Status')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                            Forms\Components\Toggle::make('is_online')
                                                ->label('Online')
                                                ->default(true),
                                            Forms\Components\Select::make('is_verified')
                                                ->label('Verification Status')
                                                ->options([
                                                    0 => 'Pending',
                                                    1 => 'Verified',
                                                    2 => 'Rejected',
                                                ])
                                                ->default(0),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('mobile_verified')
                                                ->label('Mobile Verified')
                                                ->default(false),
                                            Forms\Components\Toggle::make('alternate_mobile_verified')
                                                ->label('Alternate Mobile Verified')
                                                ->default(false),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Background Check')
                                ->schema([
                                    Forms\Components\Select::make('background_check_status')
                                        ->label('Background Check Status')
                                        ->options([
                                            'not_started' => 'Not Started',
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                        ])
                                        ->default('not_started'),
                                    Forms\Components\FileUpload::make('id_proof')
                                        ->label('ID Proof Document')
                                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                                        ->maxSize(5120),
                                ]),

                            Forms\Components\Section::make('Performance Metrics')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('avg_rating')
                                                ->label('Average Rating')
                                                ->numeric()
                                                ->step(0.1)
                                                ->minValue(0)
                                                ->maxValue(5)
                                                ->disabled(),
                                            Forms\Components\TextInput::make('total_ratings')
                                                ->label('Total Ratings')
                                                ->numeric()
                                                ->minValue(0)
                                                ->disabled(),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Additional Information')
                                ->schema([
                                    Forms\Components\Textarea::make('additional_notes')
                                        ->label('Additional Notes')
                                        ->rows(4)
                                        ->maxLength(1000),
                                    Forms\Components\FileUpload::make('profile_picture')
                                        ->label('Profile Picture')
                                        ->image()
                                        ->maxSize(2048),
                                    Forms\Components\FileUpload::make('work_portfolio')
                                        ->label('Work Portfolio Images')
                                        ->image()
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->maxSize(2048),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                // 👇 Combine first_name + last_name
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Provider')
                    ->getStateUsing(fn($record) => trim($record->first_name . ' ' . $record->last_name))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->getStateUsing(fn($record) => self::getRoleNames($record->intrested_role)),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registration')
                    ->dateTime('d M Y')
                    ->sortable(),


                Tables\Columns\TextColumn::make('is_verified')
                    ->label('Status')
                    ->badge()
                    ->icon(fn($state) => match ($state) {
                        1 => 'heroicon-o-check-circle',
                        2 => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn($state) => match ($state) {
                        1 => 'success',   // Filament’s green
                        2 => 'danger',    // Filament’s red
                        default => 'warning', // Filament’s yellow
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => 'Verified',
                        2 => 'Rejected',
                        default => 'Pending',
                    }),

                Tables\Columns\TextColumn::make('contact')
                    ->label('Contact')
                    ->getStateUsing(function ($record) {
                        $mobile = $record->mobile1_number;
                        if (!$mobile) return null;

                        $whatsappUrl = 'https://wa.me/' . preg_replace('/\D/', '', $mobile);
                        $callUrl = 'tel:' . preg_replace('/\D/', '', $mobile);

                        return view('filament.columns.contact-icons', [
                            'whatsappUrl' => $whatsappUrl,
                            'callUrl' => $callUrl,
                        ]);
                    })
                    ->sortable(false),




            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Delete Selected'),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            // 👉 Add relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceProviders::route('/'),
            'create' => Pages\CreateServiceProvider::route('/create'),
            'edit' => Pages\EditServiceProvider::route('/{record}/edit'),
        ];
    }

    // 🧭 Custom page title (for sidebar & breadcrumb)
    public static function getNavigationLabel(): string
    {
        return 'Manage Providers';
    }

    public static function getNavigationGroup(): string
    {
        return 'Admin Section';
    }

    public static function getPluralModelLabel(): string
    {
        return 'All Providers';
    }

    public static function getModelLabel(): string
    {
        return 'Provider';
    }

    /**
     * Get role names for interested_role IDs
     */
    public static function getRoleNames($interestedRoleIds): string
    {
        // Normalize to array
        if (is_string($interestedRoleIds)) {
            $decoded = json_decode($interestedRoleIds, true);
            $ids = is_array($decoded) ? $decoded : (is_int($decoded) ? [$decoded] : []);
        } elseif (is_array($interestedRoleIds)) {
            $ids = $interestedRoleIds;
        } elseif (is_int($interestedRoleIds)) {
            $ids = [$interestedRoleIds];
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return '-';
        }

        $roles = self::$role_model::whereIn('zoho_job_role_id', $ids)
            ->pluck('role_name')
            ->toArray();

        return implode(', ', $roles);
    }
}
