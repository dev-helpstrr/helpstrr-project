<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Customer;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\Service;
use App\Models\CustomerAddress;
use App\Models\TaskPriceComponent;
use App\Services\PriceCalculationService;
use App\Services\TaskAllocationService;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnifiedBookingController extends Controller
{
    protected $priceCalculationService;
    protected $taskAllocationService;

    public function __construct(
        PriceCalculationService $priceCalculationService,
        TaskAllocationService $taskAllocationService
    ) {
        $this->priceCalculationService = $priceCalculationService;
        $this->taskAllocationService = $taskAllocationService;
    }

    /**
     * Create a unified booking for any service
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createBooking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'customer_address_id' => 'required|exists:customer_addresses,id',
                'service_id' => 'required|exists:services,id',
                'pax_count' => 'nullable|integer|min:1|max:50',
                'requested_hours' => 'required|integer|min:1|max:24',
                'dates' => 'required|array|min:1',
                'dates.*' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'recurrence_type' => 'required|in:one_time,two_days,three_days',
                'dietary_preference_id' => 'nullable|exists:dietary_preferences,id',
                'special_instructions' => 'nullable|string|max:1000',
                'selected_cuisines' => 'nullable|array',
                'selected_cuisines.*' => 'exists:chef_cuisines,id',
                'addon_flags' => 'nullable|array',
                'addon_flags.*' => 'exists:chef_addon_flags,id',
                'optional_flags' => 'nullable|array',
                'optional_flags.*' => 'exists:optional_flags,id',
                'is_instant' => 'boolean',
                'scheduled_at' => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Validate token
            $tokenCheck = AuthHelper::validateToken('customers', $data['phone'], $data['token'], 'phone');
            if (!$tokenCheck['valid']) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => $tokenCheck['status_code'],
                    'status_message' => $tokenCheck['message'],
                    'data' => null
                ], $tokenCheck['status_code']);
            }

            // Get customer and validate address relationship
            $customer = Customer::where('phone', $data['phone'])->first();
            $customerAddress = $customer->addresses()->find($data['customer_address_id']);
            
            if (!$customerAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer address'
                ], 400);
            }

            // Get service and validate hierarchy
            $service = Service::with(['subcategories.categories'])->find($data['service_id']);
            
            if (!$service || !$service->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not available'
                ], 400);
            }

            // Get the primary category and subcategory for this service
            $primarySubcategory = $service->subcategories()
                ->wherePivot('is_primary', true)
                ->first();
                
            if (!$primarySubcategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service configuration error'
                ], 400);
            }

            $primaryCategory = $primarySubcategory->categories()
                ->wherePivot('is_primary', true)
                ->first();

            // Validate service-specific requirements
            $validationResult = $this->validateServiceRequirements($service, $data);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 400);
            }

            // Set scheduled_at based on instant booking or scheduled
            if ($data['is_instant'] ?? false) {
                $scheduledAt = now()->addMinutes(30); // 30 minutes from now for instant booking
            } else {
                $scheduledAt = isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : Carbon::parse($data['dates'][0] . ' ' . $data['start_time']);
            }

            // Validate lead time (minimum 2 hours for scheduled bookings)
            if (!($data['is_instant'] ?? false) && $scheduledAt->isBefore(now()->addHours(2))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduled bookings must be at least 2 hours in advance'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Create task with new category system
                $task = Task::create([
                    'customer_id' => $customer->id,
                    'customer_address_id' => $data['customer_address_id'],
                    'category_id' => $primaryCategory ? $primaryCategory->id : null,
                    'subcategory_id' => $primarySubcategory->id,
                    'service_id' => $data['service_id'],
                    'pax_count' => $data['pax_count'] ?? ($service->pax_required ? $service->min_pax : 1),
                    'requested_hours' => $data['requested_hours'],
                    'billable_hours' => $data['requested_hours'], // Initially same as requested
                    'dates' => $data['dates'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'recurrence_type' => $data['recurrence_type'],
                    'dietary_preference_id' => $data['dietary_preference_id'] ?? null,
                    'status' => Task::STATUS_REQUESTED,
                    'scheduled_at' => $scheduledAt,
                    'special_instructions' => $data['special_instructions'] ?? null,
                    'total_amount' => 0, // Will be updated after price calculation
                    'final_amount' => 0, // Will be updated after price calculation
                    'is_active' => true,
                ]);

                // Generate OTPs
                $task->generateStartOTP();
                $task->generateEndOTP();

                // Calculate pricing
                $priceComponents = $this->priceCalculationService->calculateTaskPricing($task);
                
                // Create price components
                TaskPriceComponent::create(array_merge(
                    ['task_id' => $task->id],
                    $priceComponents
                ));

                // Update task with calculated amounts
                $task->update([
                    'total_amount' => $priceComponents['total_excl_gst'],
                    'gst_amount' => $priceComponents['gst_amount'],
                    'final_amount' => $priceComponents['total_incl_gst'],
                ]);

                // Attach service-specific data
                $this->attachServiceSpecificData($task, $data);

                // Start allocation process
                $task->update(['status' => Task::STATUS_SEARCHING]);
                
                // Trigger allocation service (async)
                $this->taskAllocationService->initiateAllocation($task);

                DB::commit();

                // Load relationships for response
                $task->load([
                    'customer',
                    'customerAddress',
                    'service',
                    'priceComponents',
                    'selectedCuisines',
                    'addonFlags',
                    'optionalFlags'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data' => [
                        'task' => $this->formatTaskResponse($task),
                        'pricing' => $task->priceComponents->getPriceBreakdown(),
                        'estimated_assignment_time' => '5-10 minutes'
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get pricing preview for any service
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPricingPreview(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'service_id' => 'required|exists:services,id',
                'pax_count' => 'nullable|integer|min:1|max:50',
                'requested_hours' => 'required|integer|min:1|max:24',
                'scheduled_at' => 'required|date|after:now',
                'customer_latitude' => 'nullable|numeric',
                'customer_longitude' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Validate token
            $tokenCheck = AuthHelper::validateToken('customers', $data['phone'], $data['token'], 'phone');
            if (!$tokenCheck['valid']) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => $tokenCheck['status_code'],
                    'status_message' => $tokenCheck['message'],
                    'data' => null
                ], $tokenCheck['status_code']);
            }

            $service = Service::find($data['service_id']);
            
            // Create temporary task object for pricing calculation
            $tempTask = new Task([
                'service_id' => $data['service_id'],
                'pax_count' => $data['pax_count'] ?? ($service->pax_required ? $service->min_pax : 1),
                'requested_hours' => $data['requested_hours'],
                'billable_hours' => $data['requested_hours'],
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
            ]);

            // Calculate pricing
            $priceComponents = $this->priceCalculationService->calculateTaskPricing($tempTask);

            return response()->json([
                'success' => true,
                'message' => 'Pricing calculated successfully',
                'data' => [
                    'pricing' => $priceComponents,
                    'breakdown' => $this->priceCalculationService->getPriceBreakdown($priceComponents),
                    'estimated_duration' => $data['requested_hours'] . ' hours',
                    'surge_info' => $this->priceCalculationService->getSurgeInfo($tempTask),
                    'service_info' => [
                        'name' => $service->name,
                        'base_price' => $service->base_price,
                        'hourly_rate' => $service->hourly_rate,
                        'min_hours' => $service->min_hours,
                        'max_hours' => $service->max_hours,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all available services organized by categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableServices(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'category_id' => 'nullable|exists:new_categories,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'search' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Validate token
            $tokenCheck = AuthHelper::validateToken('customers', $data['phone'], $data['token'], 'phone');
            if (!$tokenCheck['valid']) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => $tokenCheck['status_code'],
                    'status_message' => $tokenCheck['message'],
                    'data' => null
                ], $tokenCheck['status_code']);
            }

            $categoriesQuery = NewCategory::with(['subcategories.services'])
                ->where('is_active', true);

            if (!empty($data['category_id'])) {
                $categoriesQuery->where('id', $data['category_id']);
            }

            if (!empty($data['search'])) {
                $categoriesQuery->where(function ($q) use ($data) {
                    $q->where('name', 'like', '%' . $data['search'] . '%')
                      ->orWhere('description', 'like', '%' . $data['search'] . '%');
                });
            }

            $categories = $categoriesQuery->ordered()->get();

            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'subcategories' => $category->subcategories->where('is_active', true)->map(function ($subcategory) {
                        return [
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'slug' => $subcategory->slug,
                            'description' => $subcategory->description,
                            'icon' => $subcategory->icon,
                            'color' => $subcategory->color,
                            'services' => $subcategory->services->where('is_active', true)->map(function ($service) {
                                return [
                                    'id' => $service->id,
                                    'name' => $service->name,
                                    'slug' => $service->slug,
                                    'description' => $service->description,
                                    'short_description' => $service->short_description,
                                    'icon' => $service->icon,
                                    'base_price' => $service->base_price,
                                    'hourly_rate' => $service->hourly_rate,
                                    'min_hours' => $service->min_hours,
                                    'max_hours' => $service->max_hours,
                                    'pax_required' => $service->pax_required,
                                    'min_pax' => $service->min_pax,
                                    'max_pax' => $service->max_pax,
                                    'is_event_service' => $service->is_event_service,
                                    'is_takeaway' => $service->is_takeaway,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Services retrieved successfully',
                'data' => [
                    'categories' => $formattedCategories,
                    'total_categories' => $categories->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate service-specific requirements
     */
    private function validateServiceRequirements(Service $service, array $data): array
    {
        // Check pax requirements
        if ($service->pax_required) {
            $paxCount = $data['pax_count'] ?? 1;
            if ($paxCount < $service->min_pax || ($service->max_pax && $paxCount > $service->max_pax)) {
                return [
                    'valid' => false,
                    'message' => "Pax count must be between {$service->min_pax} and {$service->max_pax}"
                ];
            }
        }

        // Check hour requirements
        $requestedHours = $data['requested_hours'];
        if ($requestedHours < $service->min_hours || ($service->max_hours && $requestedHours > $service->max_hours)) {
            return [
                'valid' => false,
                'message' => "Service duration must be between {$service->min_hours} and {$service->max_hours} hours"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Attach service-specific data to task
     */
    private function attachServiceSpecificData(Task $task, array $data): void
    {
        // Attach selected cuisines (for chef tasks)
        if (!empty($data['selected_cuisines'])) {
            $task->selectedCuisines()->attach($data['selected_cuisines']);
        }

        // Attach addon flags (for chef tasks)
        if (!empty($data['addon_flags'])) {
            $task->addonFlags()->attach($data['addon_flags']);
        }

        // Attach optional flags
        if (!empty($data['optional_flags'])) {
            $task->optionalFlags()->attach($data['optional_flags']);
        }
    }

    /**
     * Format task response for API
     */
    private function formatTaskResponse(Task $task): array
    {
        return [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'status' => $task->status,
            'service' => [
                'id' => $task->service->id,
                'name' => $task->service->name,
                'icon' => $task->service->icon,
            ],
            'schedule' => [
                'scheduled_at' => $task->scheduled_at->toISOString(),
                'dates' => $task->dates,
                'start_time' => $task->start_time,
                'end_time' => $task->end_time,
                'duration_hours' => $task->requested_hours,
                'recurrence_type' => $task->recurrence_type,
            ],
            'pricing' => [
                'total_amount' => $task->total_amount,
                'gst_amount' => $task->gst_amount,
                'final_amount' => $task->final_amount,
                'currency' => 'INR',
            ],
            'details' => [
                'pax_count' => $task->pax_count,
                'special_instructions' => $task->special_instructions,
            ],
            'created_at' => $task->created_at->toISOString(),
        ];
    }
}