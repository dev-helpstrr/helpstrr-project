<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\ChefBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ChefBookingController extends Controller
{
    protected ChefBookingService $chefBookingService;

    public function __construct(ChefBookingService $chefBookingService)
    {
        $this->chefBookingService = $chefBookingService;
    }

    /**
     * Layer 1: Get available chef services
     */
    public function getServices(): JsonResponse
    {
        $result = $this->chefBookingService->getAvailableServices();
        return response()->json($result);
    }

    /**
     * Layer 2: Get available cuisines for selected subcategory
     */
    public function getCuisines(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->chefBookingService->getAvailableCuisines($request->subcategory_id);
        return response()->json($result);
    }

    /**
     * Layer 3: Get available dietary preferences
     */
    public function getDietaryPreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'selected_cuisines' => 'nullable|array',
            'selected_cuisines.*' => 'exists:chef_cuisines,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->chefBookingService->getAvailableDietaryPreferences(
            $request->subcategory_id,
            $request->selected_cuisines ?? []
        );

        return response()->json($result);
    }

    /**
     * Layer 4: Get available optional flags
     */
    public function getOptionalFlags(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'selected_cuisines' => 'nullable|array',
            'selected_cuisines.*' => 'exists:chef_cuisines,id',
            'dietary_preference_id' => 'nullable|exists:dietary_preferences,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->chefBookingService->getAvailableOptionalFlags(
            $request->subcategory_id,
            $request->selected_cuisines ?? [],
            $request->dietary_preference_id
        );

        return response()->json($result);
    }

    /**
     * Layer 5: Get pax and time options
     */
    public function getPaxAndTimeOptions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->chefBookingService->getPaxAndTimeOptions($request->subcategory_id);
        return response()->json($result);
    }

    /**
     * Layer 6: Get customer addresses
     */
    public function getAddresses(): JsonResponse
    {
        $customer = Auth::user();
        $result = $this->chefBookingService->getCustomerAddresses($customer->id);
        return response()->json($result);
    }

    /**
     * Get complete booking flow data
     */
    public function getCompleteFlow(): JsonResponse
    {
        $customer = Auth::user();
        $result = $this->chefBookingService->getCompleteBookingFlow($customer->id);
        return response()->json($result);
    }

    /**
     * Validate chef booking data
     */
    public function validateBooking(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $bookingData = array_merge($request->all(), [
            'customer_id' => $customer->id,
        ]);

        $result = $this->chefBookingService->validateChefBooking($bookingData);

        return response()->json([
            'success' => $result['valid'],
            'errors' => $result['errors'] ?? [],
            'message' => $result['valid'] ? 'Booking data is valid' : 'Validation failed'
        ]);
    }

    /**
     * Get pricing preview for chef booking
     */
    public function getPricingPreview(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'requested_hours' => 'required|integer|min:1|max:12',
            'scheduled_at' => 'required|date|after:now',
            'pax_count' => 'nullable|integer|min:1|max:50',
            'selected_cuisines' => 'required|array|min:1|max:5',
            'selected_cuisines.*' => 'exists:chef_cuisines,id',
            'dietary_preference_id' => 'nullable|exists:dietary_preferences,id',
            'addon_flags' => 'nullable|array',
            'addon_flags.*' => 'exists:chef_addon_flags,id',
            'optional_flags' => 'nullable|array',
            'optional_flags.*' => 'exists:optional_flags,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bookingData = array_merge($validator->validated(), [
            'customer_id' => $customer->id,
            'category_id' => \App\Models\NewCategory::where('slug', 'chef')->first()->id,
        ]);

        $result = $this->chefBookingService->getPricingPreview($bookingData);
        return response()->json($result);
    }

    /**
     * Get chef booking rules and constraints
     */
    public function getBookingRules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'lead_time_hours' => 2,
                'max_cuisines' => 5,
                'min_cuisines' => 1,
                'max_hours_per_booking' => 12,
                'min_hours_per_booking' => 1,
                'max_pax' => 50,
                'min_pax' => 1,
                'recurrence_options' => [
                    'one_time' => 'One Time',
                    'two_days' => 'Two Days',
                    'three_days' => 'Three Days',
                ],
                'night_time_definition' => '10 PM - 6 AM',
                'booking_flow' => [
                    'layer_1' => 'Service Selection (Category + Subcategory)',
                    'layer_2' => 'Cuisine Selection (Multi-select, 1-5 cuisines)',
                    'layer_3' => 'Dietary Preferences (Single select, optional)',
                    'layer_4' => 'Optional Flags (Multi-select with price modifiers)',
                    'layer_5' => 'Pax & Time Selection (Pax count, hours, date/time)',
                    'layer_6' => 'Address Selection (Customer addresses)',
                ],
                'validation_rules' => [
                    'lead_time' => 'Booking must be scheduled at least 2 hours in advance',
                    'cuisine_selection' => 'At least 1 cuisine must be selected, maximum 5',
                    'pax_requirement' => 'Some services require pax count specification',
                    'minimum_hours' => 'Each service has a minimum hour requirement',
                    'night_surcharge' => 'Additional charges apply for night bookings',
                    'hard_filters' => 'Some optional flags are mandatory if selected',
                ]
            ]
        ]);
    }

    /**
     * Get chef service availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'scheduled_at' => 'required|date|after:now',
            'selected_cuisines' => 'required|array|min:1|max:5',
            'selected_cuisines.*' => 'exists:chef_cuisines,id',
            'dietary_preference_id' => 'nullable|exists:dietary_preferences,id',
            'addon_flags' => 'nullable|array',
            'addon_flags.*' => 'exists:chef_addon_flags,id',
            'optional_flags' => 'nullable|array',
            'optional_flags.*' => 'exists:optional_flags,id',
            'pax_count' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Auth::user();
        $customerAddress = \App\Models\CustomerAddress::find($request->customer_address_id);

        if (!$customerAddress || $customerAddress->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer address'
            ], 400);
        }

        // Create a mock task to check availability using allocation engine
        $mockTaskData = array_merge($validator->validated(), [
            'customer_id' => $customer->id,
            'category_id' => \App\Models\NewCategory::where('slug', 'chef')->first()->id,
            'requested_hours' => 2, // Default for availability check
        ]);

        try {
            $allocationEngine = app(\App\Services\AllocationEngine::class);
            
            // Create a temporary task object for availability check
            $mockTask = new \App\Models\Task($mockTaskData);
            $mockTask->customerAddress = $customerAddress;
            $mockTask->selectedCuisines = collect($request->selected_cuisines)->map(fn($id) => (object)['id' => $id]);
            $mockTask->addonFlags = collect($request->addon_flags ?? [])->map(fn($id) => (object)['id' => $id]);
            $mockTask->optionalFlags = collect($request->optional_flags ?? [])->map(fn($id) => (object)['id' => $id]);

            // Apply capability filters to check availability
            $reflection = new \ReflectionClass($allocationEngine);
            $method = $reflection->getMethod('applyCapabilityFilters');
            $method->setAccessible(true);
            $eligibleSPs = $method->invoke($allocationEngine, $mockTask);

            $availableCount = $eligibleSPs->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $availableCount > 0,
                    'available_service_providers' => $availableCount,
                    'message' => $availableCount > 0 
                        ? "Found {$availableCount} available chef(s) for your requirements"
                        : 'No chefs available for the selected criteria. Please try different options.',
                    'suggestions' => $availableCount === 0 ? [
                        'Try selecting different cuisines',
                        'Check if dietary preferences are too restrictive',
                        'Consider removing some optional flags',
                        'Try a different time slot',
                    ] : []
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to check availability: ' . $e->getMessage()
            ], 500);
        }
    }
}