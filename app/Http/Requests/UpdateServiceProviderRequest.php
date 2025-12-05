<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $serviceProviderId = $this->route('serviceProvider')->id ?? null;

        return [
            // Basic Information
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('s_p_users', 'email')->ignore($serviceProviderId),
            ],
            'mobile1_number' => 'required|string|max:15',
            'alternate_mobile' => 'nullable|string|max:15',
            'whatsapp' => 'nullable|string|max:15',
            'dob' => 'nullable|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'age' => 'nullable|integer|min:18|max:70',
            'gender' => 'required|in:male,female,other',
            'languages_known' => 'nullable|array',
            'languages_known.*' => 'string|max:50',

            // Address & Location
            'address' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'nullable|digits:6',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            // Service Categories
            'service_categories' => 'required|array|min:1',
            'service_categories.*' => 'in:house_help,driver,chef',

            // Experience & Skills
            'experience_years' => 'nullable|integer|min:0|max:50',
            'prior_experience' => 'nullable|string|max:200',
            'bio' => 'nullable|string|max:1000',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:100',

            // Work Preferences
            'expected_hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'expected_daily_rate' => 'nullable|numeric|min:0|max:99999.99',
            'max_daily_working_hours' => 'nullable|integer|min:1|max:16',
            'max_travel_distance' => 'nullable|integer|min:1|max:100',
            'can_work_weekends' => 'boolean',
            'can_work_nights' => 'boolean',
            'preferred_working_areas' => 'nullable|array',
            'preferred_working_areas.*' => 'string|max:100',
            'special_conditions' => 'nullable|string|max:500',
            'additional_notes' => 'nullable|string|max:1000',

            // Status Fields
            'is_active' => 'boolean',
            'is_verified' => 'in:0,1,2',
            'is_online' => 'boolean',
            'mobile_verified' => 'boolean',
            'alternate_mobile_verified' => 'boolean',
            'background_check_status' => 'in:not_started,pending,approved,rejected',

            // Performance Metrics (usually read-only, but allowing updates for admin)
            'avg_rating' => 'nullable|numeric|min:0|max:5',
            'total_ratings' => 'nullable|integer|min:0',

            // Driver Details
            'driver_detail' => 'array',
            'driver_detail.license_number' => 'nullable|string|max:20',
            'driver_detail.license_expiry_date' => 'nullable|date|after:today',
            'driver_detail.years_of_experience' => 'nullable|integer|min:0|max:50',
            'driver_detail.transmission_types' => 'nullable|array',
            'driver_detail.transmission_types.*' => 'in:manual,automatic',
            'driver_detail.vehicle_segments' => 'nullable|array',
            'driver_detail.vehicle_segments.*' => 'in:hatchback,sedan,suv,mpv',
            'driver_detail.city_driving_experience' => 'boolean',
            'driver_detail.highway_driving_experience' => 'boolean',
            'driver_detail.night_driving_experience' => 'boolean',
            'driver_detail.traffic_heavy_experience' => 'boolean',
            'driver_detail.ready_for_outstation' => 'boolean',
            'driver_detail.ready_for_oneway_outstation' => 'boolean',
            'driver_detail.ready_for_roundtrip_outstation' => 'boolean',
            'driver_detail.ready_for_airport_pickup' => 'boolean',
            'driver_detail.ready_for_office_commute' => 'boolean',
            'driver_detail.comfortable_long_hours' => 'boolean',
            'driver_detail.comfortable_luggage_handling' => 'boolean',
            'driver_detail.comfortable_waiting_time' => 'boolean',
            'driver_detail.expected_hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'driver_detail.expected_daily_rate' => 'nullable|numeric|min:0|max:99999.99',
            'driver_detail.outstation_per_km_rate' => 'nullable|numeric|min:0|max:999.99',

            // Chef Details
            'chef_detail' => 'array',
            'chef_detail.years_of_cooking_experience' => 'nullable|integer|min:0|max:50',
            'chef_detail.chef_type' => 'nullable|in:home_cook,event_cook,specialist_cook',
            'chef_detail.cuisine_specialties' => 'nullable|array',
            'chef_detail.cuisine_specialties.*' => 'in:bengali,north_indian,south_indian,chinese,continental,mughlai,snacks,biryani',
            'chef_detail.pax_capacity' => 'nullable|array',
            'chef_detail.pax_capacity.*' => 'in:1-4,5-10,10-25,25-50,50+',
            'chef_detail.can_cook_breakfast' => 'boolean',
            'chef_detail.can_cook_lunch' => 'boolean',
            'chef_detail.can_cook_dinner' => 'boolean',
            'chef_detail.can_cook_full_day' => 'boolean',
            'chef_detail.can_cook_special_occasions' => 'boolean',
            'chef_detail.dietary_preference' => 'nullable|in:veg_only,veg_nonveg,jain_food,all',
            'chef_detail.can_bring_utensils' => 'boolean',
            'chef_detail.can_bring_raw_materials' => 'boolean',
            'chef_detail.hygiene_certification' => 'nullable|string|max:100',
            'chef_detail.price_per_meal' => 'nullable|numeric|min:0|max:9999.99',
            'chef_detail.price_per_day' => 'nullable|numeric|min:0|max:99999.99',
            'chef_detail.price_per_event' => 'nullable|numeric|min:0|max:999999.99',
            'chef_detail.speciality_description' => 'nullable|string|max:1000',

            // House Help Details
            'house_help_detail' => 'array',
            'house_help_detail.can_clean_utensils' => 'boolean',
            'house_help_detail.can_sweep_mop' => 'boolean',
            'house_help_detail.can_dust' => 'boolean',
            'house_help_detail.can_wash_clothes' => 'boolean',
            'house_help_detail.can_iron_clothes' => 'boolean',
            'house_help_detail.can_clean_bathroom' => 'boolean',
            'house_help_detail.can_clean_kitchen' => 'boolean',
            'house_help_detail.can_deep_clean' => 'boolean',
            'house_help_detail.comfortable_with_pets' => 'boolean',
            'house_help_detail.can_assist_elderly' => 'boolean',
            'house_help_detail.can_handle_babies' => 'boolean',
            'house_help_detail.can_cook_basic_meals' => 'boolean',
            'house_help_detail.can_grocery_shopping' => 'boolean',
            'house_help_detail.can_gardening' => 'boolean',
            'house_help_detail.can_car_cleaning' => 'boolean',
            'house_help_detail.min_hours_per_day' => 'nullable|integer|min:1|max:12',
            'house_help_detail.max_hours_per_day' => 'nullable|integer|min:1|max:16',
            'house_help_detail.max_hours_per_task' => 'nullable|integer|min:1|max:8',
            'house_help_detail.available_weekends' => 'boolean',
            'house_help_detail.available_festivals' => 'boolean',
            'house_help_detail.has_own_cleaning_supplies' => 'boolean',
            'house_help_detail.has_own_equipment' => 'boolean',
            'house_help_detail.available_equipment' => 'nullable|array',
            'house_help_detail.available_equipment.*' => 'string|max:100',
            'house_help_detail.preferred_work_timings' => 'nullable|array',
            'house_help_detail.hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'house_help_detail.daily_rate' => 'nullable|numeric|min:0|max:99999.99',
            'house_help_detail.monthly_rate' => 'nullable|numeric|min:0|max:9999999.99',

            // Availability Schedules
            'availability_schedules' => 'nullable|array',
            'availability_schedules.*.day_of_week' => 'integer|between:0,6',
            'availability_schedules.*.start_time' => 'nullable|date_format:H:i',
            'availability_schedules.*.end_time' => 'nullable|date_format:H:i|after:availability_schedules.*.start_time',
            'availability_schedules.*.break_start_time' => 'nullable|date_format:H:i',
            'availability_schedules.*.break_end_time' => 'nullable|date_format:H:i|after:availability_schedules.*.break_start_time',
            'availability_schedules.*.is_available' => 'boolean',
            'availability_schedules.*.notes' => 'nullable|string|max:200',

            // File Uploads
            'profile_picture' => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'work_portfolio' => 'nullable|array',
            'work_portfolio.*' => 'image|max:2048',
            'driver_detail.license_front_image' => 'nullable|image|max:2048',
            'driver_detail.license_back_image' => 'nullable|image|max:2048',
            'chef_detail.food_portfolio_images' => 'nullable|array',
            'chef_detail.food_portfolio_images.*' => 'image|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'mobile1_number.required' => 'Primary mobile number is required.',
            'gender.required' => 'Gender selection is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'service_categories.required' => 'At least one service category must be selected.',
            'service_categories.min' => 'At least one service category must be selected.',
            'dob.before' => 'Service provider must be at least 18 years old.',
            'age.min' => 'Service provider must be at least 18 years old.',
            'email.unique' => 'This email address is already registered.',
            'pincode.digits' => 'PIN code must be exactly 6 digits.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'driver_detail.license_expiry_date.after' => 'License expiry date must be in the future.',
            'availability_schedules.*.end_time.after' => 'End time must be after start time.',
            'availability_schedules.*.break_end_time.after' => 'Break end time must be after break start time.',
            'is_verified.in' => 'Verification status must be Pending (0), Verified (1), or Rejected (2).',
            'background_check_status.in' => 'Background check status must be one of: not_started, pending, approved, rejected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'mobile1_number' => 'primary mobile number',
            'alternate_mobile' => 'alternate mobile number',
            'dob' => 'date of birth',
            'service_categories' => 'service categories',
            'experience_years' => 'years of experience',
            'expected_hourly_rate' => 'expected hourly rate',
            'expected_daily_rate' => 'expected daily rate',
            'max_daily_working_hours' => 'maximum daily working hours',
            'max_travel_distance' => 'maximum travel distance',
            'is_active' => 'active status',
            'is_verified' => 'verification status',
            'is_online' => 'online status',
            'background_check_status' => 'background check status',
            'driver_detail.license_number' => 'license number',
            'driver_detail.license_expiry_date' => 'license expiry date',
            'driver_detail.years_of_experience' => 'years of driving experience',
            'chef_detail.years_of_cooking_experience' => 'years of cooking experience',
            'chef_detail.price_per_meal' => 'price per meal',
            'chef_detail.price_per_day' => 'price per day',
            'chef_detail.price_per_event' => 'price per event',
            'house_help_detail.min_hours_per_day' => 'minimum hours per day',
            'house_help_detail.max_hours_per_day' => 'maximum hours per day',
            'house_help_detail.max_hours_per_task' => 'maximum hours per task',
            'house_help_detail.hourly_rate' => 'hourly rate',
            'house_help_detail.daily_rate' => 'daily rate',
            'house_help_detail.monthly_rate' => 'monthly rate',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that category-specific details are provided when category is selected
            $categories = $this->get('service_categories', []);

            if (in_array('driver', $categories)) {
                $this->validateDriverDetails($validator);
            }

            if (in_array('chef', $categories)) {
                $this->validateChefDetails($validator);
            }

            if (in_array('house_help', $categories)) {
                $this->validateHouseHelpDetails($validator);
            }

            // Validate working hours consistency
            $this->validateWorkingHours($validator);

            // Validate rating consistency
            $this->validateRatingConsistency($validator);
        });
    }

    /**
     * Validate driver-specific details
     */
    private function validateDriverDetails($validator): void
    {
        $driverDetail = $this->get('driver_detail', []);

        if (empty($driverDetail['license_number'])) {
            $validator->errors()->add('driver_detail.license_number', 'License number is required for drivers.');
        }

        if (!isset($driverDetail['years_of_experience']) || $driverDetail['years_of_experience'] === '') {
            $validator->errors()->add('driver_detail.years_of_experience', 'Years of driving experience is required for drivers.');
        }
    }

    /**
     * Validate chef-specific details
     */
    private function validateChefDetails($validator): void
    {
        $chefDetail = $this->get('chef_detail', []);

        if (!isset($chefDetail['years_of_cooking_experience']) || $chefDetail['years_of_cooking_experience'] === '') {
            $validator->errors()->add('chef_detail.years_of_cooking_experience', 'Years of cooking experience is required for chefs.');
        }

        if (empty($chefDetail['cuisine_specialties'])) {
            $validator->errors()->add('chef_detail.cuisine_specialties', 'At least one cuisine specialty is required for chefs.');
        }
    }

    /**
     * Validate house help specific details
     */
    private function validateHouseHelpDetails($validator): void
    {
        $houseHelpDetail = $this->get('house_help_detail', []);

        // Check if at least one skill is selected
        $skills = [
            'can_clean_utensils', 'can_sweep_mop', 'can_dust', 'can_wash_clothes',
            'can_iron_clothes', 'can_clean_bathroom', 'can_clean_kitchen', 'can_deep_clean'
        ];

        $hasSkill = false;
        foreach ($skills as $skill) {
            if (!empty($houseHelpDetail[$skill])) {
                $hasSkill = true;
                break;
            }
        }

        if (!$hasSkill) {
            $validator->errors()->add('house_help_detail.skills', 'At least one cleaning skill must be selected for house help.');
        }
    }

    /**
     * Validate working hours consistency
     */
    private function validateWorkingHours($validator): void
    {
        $houseHelpDetail = $this->get('house_help_detail', []);

        if (!empty($houseHelpDetail['min_hours_per_day']) && !empty($houseHelpDetail['max_hours_per_day'])) {
            if ($houseHelpDetail['min_hours_per_day'] > $houseHelpDetail['max_hours_per_day']) {
                $validator->errors()->add('house_help_detail.min_hours_per_day', 'Minimum hours per day cannot be greater than maximum hours per day.');
            }
        }
    }

    /**
     * Validate rating consistency
     */
    private function validateRatingConsistency($validator): void
    {
        $avgRating = $this->get('avg_rating');
        $totalRatings = $this->get('total_ratings');

        if ($avgRating > 0 && $totalRatings == 0) {
            $validator->errors()->add('total_ratings', 'Total ratings must be greater than 0 when average rating is set.');
        }

        if ($totalRatings > 0 && ($avgRating <= 0 || $avgRating === null)) {
            $validator->errors()->add('avg_rating', 'Average rating must be greater than 0 when total ratings is set.');
        }
    }
}