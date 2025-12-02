<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\ChefAddonFlag;
use App\Models\OptionalFlag;
use App\Models\CustomerAddress;
use App\Services\PricingEngine;
use Carbon\Carbon;

class ChefBookingService
{
    /**
     * This implements the EXACT 6-LAYER CHEF BOOKING FLOW as per requirements:
     * 
     * Layer 1: Service Selection (Category + Subcategory)
     * Layer 2: Cuisine Selection (Multi-select from available cuisines)
     * Layer 3: Dietary Preferences (Single select)
     * Layer 4: Optional Flags (Multi-select with price modifiers)
     * Layer 5: Pax & Time Selection (Pax count, hours, date/time)
     * Layer 6: Address Selection (Customer addresses)
     */

    protected PricingEngine $pricingEngine;

    public function __construct(PricingEngine $pricingEngine)
    {
        $this->pricingEngine = $pricingEngine;
    }

    /**
     * Layer 1: Get available chef services (categories and subcategories)
     */
    public function getAvailableServices(): array
    {
        $chefCategory = Category::where('slug', 'chef')->active()->first();
        
        if (!$chefCategory) {
            return [
                'success' => false,
                'message' => 'Chef services not available'
            ];
        }

        $subcategories = $chefCategory->subcategories()
            ->active()
            ->ordered()
            ->get()
            ->map(function ($subcategory) {
                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'description' => $subcategory->description,
                    'hourly_rate' => $subcategory->hourly_rate,
                    'min_hours' => $subcategory->min_hours,
                    'consultation_fee' => $subcategory->consultation_fee,
                    'pax_required' => $subcategory->pax_required,
                    'is_event_category' => $subcategory->is_event_category,
                    'is_takeaway' => $subcategory->is_takeaway,
                    'recurrence_allowed' => $subcategory->recurrence_allowed,
                ];
            });

        return [
            'success' => true,
            'category' => [
                'id' => $chefCategory->id,
                'name' => $chefCategory->name,
                'description' => $chefCategory->description,
                'night_multiplier' => $chefCategory->night_multiplier,
            ],
            'subcategories' => $subcategories
        ];
    }

    /**
     * Layer 2: Get available cuisines for selected subcategory
     */
    public function getAvailableCuisines(int $subcategoryId): array
    {
        $subcategory = Subcategory::find($subcategoryId);
        
        if (!$subcategory || $subcategory->category->slug !== 'chef') {
            return [
                'success' => false,
                'message' => 'Invalid chef subcategory'
            ];
        }

        // Get cuisines that have at least one active SP
        $cuisines = ChefCuisine::active()
            ->whereHas('spCapabilities', function ($query) use ($subcategoryId) {
                $query->where('is_active', true)
                    ->whereHas('serviceProvider', function ($q) use ($subcategoryId) {
                        $q->active()->verified()
                          ->whereHas('capabilities', function ($cap) use ($subcategoryId) {
                              $cap->where('subcategory_id', $subcategoryId)->where('is_active', true);
                          });
                    });
            })
            ->ordered()
            ->get()
            ->map(function ($cuisine) {
                return [
                    'id' => $cuisine->id,
                    'name' => $cuisine->name,
                    'description' => $cuisine->description,
                    'available_sps' => $cuisine->getServiceProvidersCount(),
                ];
            });

        return [
            'success' => true,
            'subcategory' => [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'requires_cuisine_selection' => true,
            ],
            'cuisines' => $cuisines,
            'selection_rules' => [
                'min_selections' => 1,
                'max_selections' => 5,
                'description' => 'Select 1-5 cuisines you would like the chef to prepare'
            ]
        ];
    }

    /**
     * Layer 3: Get available dietary preferences
     */
    public function getAvailableDietaryPreferences(int $subcategoryId, array $selectedCuisines = []): array
    {
        $subcategory = Subcategory::find($subcategoryId);
        
        if (!$subcategory || $subcategory->category->slug !== 'chef') {
            return [
                'success' => false,
                'message' => 'Invalid chef subcategory'
            ];
        }

        // Get dietary preferences that have SPs available for selected cuisines
        $query = DietaryPreference::active()
            ->whereHas('spCapabilities', function ($query) use ($subcategoryId, $selectedCuisines) {
                $query->where('is_active', true)
                    ->whereHas('serviceProvider', function ($q) use ($subcategoryId, $selectedCuisines) {
                        $q->active()->verified()
                          ->whereHas('capabilities', function ($cap) use ($subcategoryId) {
                              $cap->where('subcategory_id', $subcategoryId)->where('is_active', true);
                          });
                        
                        // If cuisines are selected, ensure SP can handle them
                        if (!empty($selectedCuisines)) {
                            $q->whereHas('cuisineCapabilities', function ($cuisine) use ($selectedCuisines) {
                                $cuisine->whereIn('chef_cuisine_id', $selectedCuisines)->where('is_active', true);
                            });
                        }
                    });
            });

        $dietaryPreferences = $query->ordered()
            ->get()
            ->map(function ($preference) {
                return [
                    'id' => $preference->id,
                    'name' => $preference->name,
                    'description' => $preference->description,
                    'available_sps' => $preference->getServiceProvidersCount(),
                ];
            });

        return [
            'success' => true,
            'dietary_preferences' => $dietaryPreferences,
            'selection_rules' => [
                'required' => false,
                'single_select' => true,
                'description' => 'Select your dietary preference (optional)'
            ]
        ];
    }

    /**
     * Layer 4: Get available optional flags (addon flags + optional flags)
     */
    public function getAvailableOptionalFlags(int $subcategoryId, array $selectedCuisines = [], ?int $dietaryPreferenceId = null): array
    {
        $subcategory = Subcategory::find($subcategoryId);
        
        if (!$subcategory || $subcategory->category->slug !== 'chef') {
            return [
                'success' => false,
                'message' => 'Invalid chef subcategory'
            ];
        }

        // Get addon flags (chef-specific)
        $addonFlags = ChefAddonFlag::active()
            ->whereHas('spCapabilities', function ($query) use ($subcategoryId, $selectedCuisines, $dietaryPreferenceId) {
                $this->applyServiceProviderFilters($query, $subcategoryId, $selectedCuisines, $dietaryPreferenceId);
            })
            ->ordered()
            ->get()
            ->map(function ($flag) {
                return [
                    'id' => $flag->id,
                    'name' => $flag->name,
                    'description' => $flag->description,
                    'type' => 'addon',
                    'price_modifier' => 0, // Addon flags don't have price modifiers
                    'available_sps' => $flag->getServiceProvidersCount(),
                ];
            });

        // Get optional flags (general)
        $optionalFlags = OptionalFlag::active()
            ->whereHas('spCapabilities', function ($query) use ($subcategoryId, $selectedCuisines, $dietaryPreferenceId) {
                $this->applyServiceProviderFilters($query, $subcategoryId, $selectedCuisines, $dietaryPreferenceId);
            })
            ->ordered()
            ->get()
            ->map(function ($flag) {
                return [
                    'id' => $flag->id,
                    'name' => $flag->name,
                    'description' => $flag->description,
                    'type' => 'optional',
                    'is_hard_filter' => $flag->is_hard_filter,
                    'price_modifier' => $flag->price_modifier,
                    'available_sps' => $flag->getServiceProvidersCount(),
                ];
            });

        return [
            'success' => true,
            'addon_flags' => $addonFlags,
            'optional_flags' => $optionalFlags,
            'selection_rules' => [
                'addon_flags' => [
                    'description' => 'Select additional chef capabilities',
                    'multiple_select' => true,
                    'price_impact' => false,
                ],
                'optional_flags' => [
                    'description' => 'Select optional preferences (may affect pricing)',
                    'multiple_select' => true,
                    'price_impact' => true,
                    'hard_filters' => 'Some options are mandatory if selected',
                ]
            ]
        ];
    }

    /**
     * Layer 5: Get pax and time selection options
     */
    public function getPaxAndTimeOptions(int $subcategoryId): array
    {
        $subcategory = Subcategory::find($subcategoryId);
        
        if (!$subcategory || $subcategory->category->slug !== 'chef') {
            return [
                'success' => false,
                'message' => 'Invalid chef subcategory'
            ];
        }

        return [
            'success' => true,
            'pax_options' => [
                'required' => $subcategory->pax_required,
                'min_pax' => 1,
                'max_pax' => 50, // System limit
                'description' => $subcategory->pax_required 
                    ? 'Number of people to be served (required)' 
                    : 'Number of people to be served (optional)',
            ],
            'time_options' => [
                'min_hours' => $subcategory->min_hours,
                'max_hours' => 12, // System limit
                'hourly_rate' => $subcategory->hourly_rate,
                'consultation_fee' => $subcategory->consultation_fee,
                'description' => "Minimum {$subcategory->min_hours} hours required",
            ],
            'recurrence_options' => [
                'allowed' => $subcategory->recurrence_allowed,
                'options' => $subcategory->recurrence_allowed ? [
                    'one_time' => 'One Time',
                    'two_days' => 'Two Days',
                    'three_days' => 'Three Days',
                ] : ['one_time' => 'One Time'],
            ],
            'scheduling_rules' => [
                'lead_time_hours' => 2,
                'description' => 'Booking must be scheduled at least 2 hours in advance',
                'night_surcharge' => $subcategory->category->night_multiplier > 1 ? [
                    'multiplier' => $subcategory->category->night_multiplier,
                    'applies_to' => 'Bookings between 10 PM - 6 AM',
                ] : null,
            ]
        ];
    }

    /**
     * Layer 6: Get customer addresses
     */
    public function getCustomerAddresses(int $customerId): array
    {
        $addresses = CustomerAddress::where('customer_id', $customerId)
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($address) {
                return [
                    'id' => $address->id,
                    'label' => $address->getLabelDisplayAttribute(),
                    'full_address' => $address->getFullAddressAttribute(),
                    'short_address' => $address->getShortAddressAttribute(),
                    'is_default' => $address->is_default,
                    'has_coordinates' => $address->hasCoordinates(),
                    'city' => $address->city,
                    'pincode' => $address->pincode,
                ];
            });

        return [
            'success' => true,
            'addresses' => $addresses,
            'can_add_new' => true,
            'selection_rules' => [
                'required' => true,
                'description' => 'Select the address where chef service is required',
            ]
        ];
    }

    /**
     * Validate complete chef booking data
     */
    public function validateChefBooking(array $bookingData): array
    {
        $errors = [];

        // Layer 1: Service selection
        if (empty($bookingData['subcategory_id'])) {
            $errors[] = 'Service selection is required';
        } else {
            $subcategory = Subcategory::find($bookingData['subcategory_id']);
            if (!$subcategory || $subcategory->category->slug !== 'chef') {
                $errors[] = 'Invalid chef service selected';
            }
        }

        // Layer 2: Cuisine selection
        if (empty($bookingData['selected_cuisines']) || !is_array($bookingData['selected_cuisines'])) {
            $errors[] = 'At least one cuisine must be selected';
        } elseif (count($bookingData['selected_cuisines']) > 5) {
            $errors[] = 'Maximum 5 cuisines can be selected';
        }

        // Layer 3: Dietary preferences (optional)
        if (!empty($bookingData['dietary_preference_id'])) {
            $dietaryPreference = DietaryPreference::find($bookingData['dietary_preference_id']);
            if (!$dietaryPreference) {
                $errors[] = 'Invalid dietary preference selected';
            }
        }

        // Layer 4: Optional flags validation (handled in individual validation)

        // Layer 5: Pax and time validation
        if (isset($subcategory) && $subcategory->pax_required && empty($bookingData['pax_count'])) {
            $errors[] = 'Number of people is required for this service';
        }

        if (empty($bookingData['requested_hours'])) {
            $errors[] = 'Duration is required';
        } elseif (isset($subcategory) && $bookingData['requested_hours'] < $subcategory->min_hours) {
            $errors[] = "Minimum {$subcategory->min_hours} hours required for this service";
        }

        if (empty($bookingData['scheduled_at'])) {
            $errors[] = 'Schedule date and time is required';
        } else {
            $scheduledAt = Carbon::parse($bookingData['scheduled_at']);
            if (!$this->pricingEngine->validateLeadTime($scheduledAt)) {
                $errors[] = 'Booking must be scheduled at least 2 hours in advance';
            }
        }

        // Layer 6: Address validation
        if (empty($bookingData['customer_address_id'])) {
            $errors[] = 'Service address is required';
        } else {
            $address = CustomerAddress::find($bookingData['customer_address_id']);
            if (!$address || $address->customer_id != $bookingData['customer_id']) {
                $errors[] = 'Invalid service address selected';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get pricing preview for chef booking
     */
    public function getPricingPreview(array $bookingData): array
    {
        $validation = $this->validateChefBooking($bookingData);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        try {
            $pricingData = $this->pricingEngine->calculateTaskPricing($bookingData);
            $customerPrice = $this->pricingEngine->getCustomerDisplayPrice($pricingData);

            return [
                'success' => true,
                'pricing' => $customerPrice,
                'breakdown' => [
                    'base_amount' => $pricingData['base_amount'],
                    'billable_hours' => $pricingData['billable_hours'],
                    'hourly_rate' => $pricingData['hourly_rate'],
                    'has_consultation_fee' => $pricingData['consultation_fee'] > 0,
                    'consultation_fee' => $pricingData['consultation_fee'],
                    'has_night_surcharge' => $pricingData['night_adjustment'] > 0,
                    'has_subscription_discount' => $pricingData['subscription_discount'] > 0,
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unable to calculate pricing: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Apply service provider filters for availability checks
     */
    private function applyServiceProviderFilters($query, int $subcategoryId, array $selectedCuisines, ?int $dietaryPreferenceId): void
    {
        $query->where('is_active', true)
            ->whereHas('serviceProvider', function ($q) use ($subcategoryId, $selectedCuisines, $dietaryPreferenceId) {
                $q->active()->verified()
                  ->whereHas('capabilities', function ($cap) use ($subcategoryId) {
                      $cap->where('subcategory_id', $subcategoryId)->where('is_active', true);
                  });
                
                // Filter by selected cuisines
                if (!empty($selectedCuisines)) {
                    $q->whereHas('cuisineCapabilities', function ($cuisine) use ($selectedCuisines) {
                        $cuisine->whereIn('chef_cuisine_id', $selectedCuisines)->where('is_active', true);
                    });
                }
                
                // Filter by dietary preference
                if ($dietaryPreferenceId) {
                    $q->whereHas('dietaryCapabilities', function ($dietary) use ($dietaryPreferenceId) {
                        $dietary->where('dietary_preference_id', $dietaryPreferenceId)->where('is_active', true);
                    });
                }
            });
    }

    /**
     * Get complete chef booking flow data
     */
    public function getCompleteBookingFlow(int $customerId): array
    {
        return [
            'layer_1' => $this->getAvailableServices(),
            'layer_6' => $this->getCustomerAddresses($customerId),
            'booking_rules' => [
                'lead_time_hours' => 2,
                'max_cuisines' => 5,
                'max_hours_per_booking' => 12,
                'max_pax' => 50,
            ]
        ];
    }
}