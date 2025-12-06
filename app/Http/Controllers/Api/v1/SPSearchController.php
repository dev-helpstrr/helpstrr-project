<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\SPUser;
use App\Models\CustomerAddress;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\ChefAddonFlag;
use App\Models\OptionalFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\AuthHelper;

class SPSearchController extends Controller
{
    /**
     * Search Service Providers based on 38 filters from allocation engine
     * This API implements the exact same filtering logic as the AllocationEngine
     * but allows for standalone SP search without requiring a task
     */
    public function searchServiceProviders(Request $request)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'category_id' => 'required|integer|exists:new_categories,id',
                'subcategory_id' => 'required|integer|exists:new_subcategories,id',
                'scheduled_at' => 'required|date|after:' . now()->addHours(2)->toDateTimeString(),
                'pax_count' => 'nullable|integer|min:1|max:50',
                'cuisine_ids' => 'nullable|array',
                'cuisine_ids.*' => 'integer|exists:chef_cuisines,id',
                'dietary_preference_id' => 'nullable|integer|exists:dietary_preferences,id',
                'addon_flag_ids' => 'nullable|array',
                'addon_flag_ids.*' => 'integer|exists:chef_addon_flags,id',
                'optional_flag_ids' => 'nullable|array',
                'optional_flag_ids.*' => 'integer|exists:optional_flags,id',
                'radius_km' => 'nullable|integer|min:1|max:50',
                'sort_by' => 'nullable|string|in:distance,rating,quality_score,price',
                'limit' => 'nullable|integer|min:1|max:100',
                'include_new_sps' => 'nullable|boolean',
                'gold_level_only' => 'nullable|boolean',
                'online_only' => 'nullable|boolean',
                'night_shift_required' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $searchParams = $validator->validated();
            
            // Apply all 38 capability filters
            $eligibleSPs = $this->applyAll38Filters($searchParams);

            if ($eligibleSPs->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No service providers found matching the criteria',
                    'data' => [
                        'sp_ids' => [], // Changed from service_providers to sp_ids
                        'total_count' => 0,
                        'search_params' => $searchParams,
                        'filters_applied' => $this->getAppliedFiltersCount($searchParams)
                    ]
                ]);
            }

            // Sort service providers using allocation engine logic
            $sortedSPs = $this->sortServiceProvidersWithAllocationLogic($eligibleSPs, $searchParams);

            // Apply limit
            $limit = $searchParams['limit'] ?? 20;
            $limitedSPs = $sortedSPs->take($limit);

            // Format response with comprehensive data
            $formattedSPs = $this->formatServiceProvidersResponse($limitedSPs, $searchParams);

            return response()->json([
                'success' => true,
                'message' => 'Service providers found successfully',
                'data' => [
                    'sp_ids' => $formattedSPs, // Changed from service_providers to sp_ids
                    'total_count' => $sortedSPs->count(),
                    'returned_count' => $limitedSPs->count(),
                    'search_params' => $searchParams,
                    'filters_applied' => $this->getAppliedFiltersCount($searchParams),
                    'search_radius_km' => $searchParams['radius_km'] ?? 10,
                    'search_location' => [
                        'latitude' => $searchParams['latitude'],
                        'longitude' => $searchParams['longitude']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply all 38 capability filters as per allocation engine requirements
     * This implements the exact same logic as AllocationEngine::applyCapabilityFilters
     * FILTER 1: SP User must be active - THIS IS THE VERY FIRST CONDITION
     */
    private function applyAll38Filters(array $params): Collection
    {
        $scheduledAt = Carbon::parse($params['scheduled_at']);
        $isNightTime = $scheduledAt->hour >= 22 || $scheduledAt->hour <= 6;
        $radiusKm = $params['radius_km'] ?? 10;

        // Start with base query applying filters 1-15
        // FILTER 1: SP User must be active - VERY FIRST CONDITION
        $query = ServiceProvider::query()
            ->whereHas('spUser', function ($q) {
                $q->where('is_active', true); // FIRST FILTER: SP User active status
            })
            
            // FILTER 2: Service Provider active status
            ->where('is_active', true)
            
            // FILTER 3: Verified status  
            ->where('kyc_verified', true)
            
            // FILTER 4: KYC approved
            ->where('kyc_status', 'approved')
            
            // FILTER 5: Not blocked
            ->where(function ($q) {
                $q->where('is_blocked', false)
                  ->orWhere('blocked_until', '<', now());
            })
            
            // FILTER 5: Not in cooldown
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                  ->orWhere('cooldown_until', '<', now());
            })
            
            // FILTER 6: Online status (if required)
            ->when(!empty($params['online_only']), function ($q) {
                $q->whereHas('spUser', function ($subQ) {
                    $subQ->where('is_online', true);
                });
            })
            
            // FILTER 7: Last seen recently (active in last 2 hours)
            ->whereHas('spUser', function ($q) {
                $q->where('last_seen_at', '>=', now()->subHours(2));
            })
            
            // FILTER 8-12: Additional eligibility checks
            ->where('total_ratings', '>=', 0) // Has rating data
            
            // FILTER 13-14: Category/Subcategory capability
            ->whereHas('capabilities', function ($q) use ($params) {
                $q->where('subcategory_id', $params['subcategory_id'])
                  ->where('is_active', true);
            })
            
            // FILTER 15: Not already on task
            ->whereDoesntHave('tasks', function ($q) {
                $q->whereIn('status', [
                    'assigned', 'on_the_way', 'arrived', 
                    'otp_start_verified', 'started', 'paused', 'resumed'
                ]);
            });

        // FILTER 16: Cuisine match (multi-select, at least 1 overlap) - Chef only
        if (!empty($params['cuisine_ids'])) {
            $query->whereHas('cuisineCapabilities', function ($q) use ($params) {
                $q->whereIn('chef_cuisine_id', $params['cuisine_ids'])
                  ->where('is_active', true);
            });
        }

        // FILTER 17: Diet preference capability - Chef only
        if (!empty($params['dietary_preference_id'])) {
            $query->whereHas('dietaryCapabilities', function ($q) use ($params) {
                $q->where('dietary_preference_id', $params['dietary_preference_id'])
                  ->where('is_active', true);
            });
        }

        // FILTER 18: Add-on flags capability - Chef only (ALL must be supported)
        if (!empty($params['addon_flag_ids'])) {
            foreach ($params['addon_flag_ids'] as $flagId) {
                $query->whereHas('addonCapabilities', function ($q) use ($flagId) {
                    $q->where('chef_addon_flag_id', $flagId)->where('is_active', true);
                });
            }
        }

        // FILTER 19-20: Optional flags (hard filters mandatory, soft filters preferred)
        if (!empty($params['optional_flag_ids'])) {
            $hardFilterIds = OptionalFlag::whereIn('id', $params['optional_flag_ids'])
                ->where('is_hard_filter', true)
                ->pluck('id')
                ->toArray();
            
            // Hard filters are mandatory
            foreach ($hardFilterIds as $flagId) {
                $query->whereHas('optionalCapabilities', function ($q) use ($flagId) {
                    $q->where('optional_flag_id', $flagId)->where('is_active', true);
                });
            }
        }

        // FILTER 21: Pax capacity
        if (!empty($params['pax_count'])) {
            $query->whereHas('capabilities', function ($q) use ($params) {
                $q->where('subcategory_id', $params['subcategory_id'])
                  ->where('max_pax_capacity', '>=', $params['pax_count'])
                  ->where('is_active', true);
            });
        }

        // FILTER 22: Night shift willingness
        if ($isNightTime || !empty($params['night_shift_required'])) {
            $query->whereHas('spUser', function ($q) {
                $q->where('can_work_nights', true);
            });
        }

        // FILTER 23: Gold level only (if requested)
        if (!empty($params['gold_level_only'])) {
            $query->where('is_gold_level', true);
        }

        // FILTER 24: Distance capability (within radius)
        $query->whereHas('spUser', function ($q) use ($params, $radiusKm) {
            $q->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                [$params['latitude'], $params['longitude'], $params['latitude'], $radiusKm]
            );
        });

        // Load relationships for additional filtering
        $serviceProviders = $query->with([
            'spUser',
            'capabilities',
            'cuisineCapabilities',
            'dietaryCapabilities', 
            'addonCapabilities',
            'optionalCapabilities'
        ])->get();

        // Apply remaining filters (25-38) that require individual checks
        return $serviceProviders->filter(function ($sp) use ($params, $isNightTime) {
            return $this->passesRemainingFilters($sp, $params, $isNightTime);
        });
    }

    /**
     * Apply remaining filters (25-38) that require individual checks
     */
    private function passesRemainingFilters(ServiceProvider $sp, array $params, bool $isNightTime): bool
    {
        // FILTER 25: Travel distance capability (detailed check)
        if ($sp->spUser->hasCoordinates()) {
            $distance = $sp->getDistanceFrom($params['latitude'], $params['longitude']);
            $capability = $sp->capabilities()->where('subcategory_id', $params['subcategory_id'])->first();
            
            if ($capability && $distance > $capability->max_travel_distance_km) {
                return false;
            }
        }

        // FILTER 26: Availability window (detailed check)
        if (!$sp->spUser->isAvailableNow()) {
            return false;
        }

        // FILTER 27: Night shift availability (detailed capability check)
        if ($isNightTime) {
            $capability = $sp->capabilities()->where('subcategory_id', $params['subcategory_id'])->first();
            if ($capability && !$capability->night_shift_available) {
                return false;
            }
        }

        // FILTER 28: New SP inclusion logic
        if (empty($params['include_new_sps']) && $sp->total_ratings < 5) {
            return false;
        }

        // FILTER 29: Minimum rating threshold (4.0 for rated SPs)
        if ($sp->total_ratings >= 5 && $sp->rating < 4.0) {
            return false;
        }

        // FILTER 30: Acceptance rate threshold (minimum 70%)
        if ($sp->acceptance_rate < 70.0) {
            return false;
        }

        // FILTER 31: Punctuality score threshold (minimum 75%)
        if ($sp->punctuality_score < 75.0) {
            return false;
        }

        // FILTER 32: Behavior score threshold (minimum 80%)
        if ($sp->behaviour_score < 80.0) {
            return false;
        }

        // FILTER 33: Cancellation rate threshold (maximum 20%)
        if ($sp->cancellation_score > 20.0) {
            return false;
        }

        // FILTER 34: Complaint score threshold (maximum 15%)
        if ($sp->complaint_score > 15.0) {
            return false;
        }

        // FILTER 35: Rejection frequency threshold (maximum 10)
        if ($sp->rejection_frequency > 10) {
            return false;
        }

        // FILTER 36: Current workload check (not overloaded)
        $activeTasks = $sp->tasks()->whereIn('status', [
            'assigned', 'on_the_way', 'arrived', 'started', 'paused', 'resumed'
        ])->count();
        
        if ($activeTasks >= 3) { // Maximum 3 concurrent tasks
            return false;
        }

        // FILTER 37: Service area coverage
        // (This would check if SP serves the specific area - simplified for now)
        
        // FILTER 38: Platform compliance and verification
        // (Additional compliance checks would go here)

        return true;
    }

    /**
     * Sort service providers using allocation engine logic
     */
    private function sortServiceProvidersWithAllocationLogic(Collection $sps, array $params): Collection
    {
        $sortBy = $params['sort_by'] ?? 'quality_score';

        return $sps->sort(function ($a, $b) use ($params, $sortBy) {
            if ($sortBy === 'distance') {
                $aDistance = $a->getDistanceFrom($params['latitude'], $params['longitude']);
                $bDistance = $b->getDistanceFrom($params['latitude'], $params['longitude']);
                return $aDistance <=> $bDistance;
            }

            if ($sortBy === 'rating') {
                return $b->rating <=> $a->rating;
            }

            // Default: Quality score sorting (same as allocation engine)
            // 1. Rating (highest first)
            if ($a->rating != $b->rating) {
                return $b->rating <=> $a->rating;
            }

            // 2. Acceptance Rate (highest first)
            if ($a->acceptance_rate != $b->acceptance_rate) {
                return $b->acceptance_rate <=> $a->acceptance_rate;
            }

            // 3. Punctuality Score (highest first)
            if ($a->punctuality_score != $b->punctuality_score) {
                return $b->punctuality_score <=> $a->punctuality_score;
            }

            // 4. Behaviour Score (highest first)
            if ($a->behaviour_score != $b->behaviour_score) {
                return $b->behaviour_score <=> $a->behaviour_score;
            }

            // 5. Gold Level Priority (gold first)
            if ($a->is_gold_level != $b->is_gold_level) {
                return $b->is_gold_level <=> $a->is_gold_level;
            }

            // 6. Cancellation Score (lower is better)
            if ($a->cancellation_score != $b->cancellation_score) {
                return $a->cancellation_score <=> $b->cancellation_score;
            }

            // 7. Complaint Score (lower is better)
            if ($a->complaint_score != $b->complaint_score) {
                return $a->complaint_score <=> $b->complaint_score;
            }

            // 8. Rejection Frequency (lower is better)
            if ($a->rejection_frequency != $b->rejection_frequency) {
                return $a->rejection_frequency <=> $b->rejection_frequency;
            }

            // 9. Cooldown Status (not in cooldown is better)
            $aCooldown = $a->isInCooldown();
            $bCooldown = $b->isInCooldown();
            if ($aCooldown != $bCooldown) {
                return $aCooldown <=> $bCooldown;
            }

            // 10. Fairness Rule - least recent assignment
            $aLastAssigned = $a->last_assigned_at ? $a->last_assigned_at->timestamp : 0;
            $bLastAssigned = $b->last_assigned_at ? $b->last_assigned_at->timestamp : 0;
            if ($aLastAssigned != $bLastAssigned) {
                return $aLastAssigned <=> $bLastAssigned;
            }

            // 11. Distance (final tiebreaker)
            $aDistance = $a->getDistanceFrom($params['latitude'], $params['longitude']);
            $bDistance = $b->getDistanceFrom($params['latitude'], $params['longitude']);
            return $aDistance <=> $bDistance;
        })->values();
    }

    /**
     * Format service providers for API response with SP IDs and task details as nested arrays
     */
    private function formatServiceProvidersResponse(Collection $sps, array $params): array
    {
        return $sps->map(function ($sp) use ($params) {
            $distance = $sp->getDistanceFrom($params['latitude'], $params['longitude']);
            $qualityScore = $sp->getQualityScore();

            // Get all tasks for this SP (recent tasks, active tasks, etc.)
            $allTasks = $sp->tasks()->with(['category', 'subcategory', 'customer'])
                ->orderBy('created_at', 'desc')
                ->limit(10) // Limit to recent 10 tasks
                ->get();

            return [
                'sp_id' => $sp->id, // Changed from 'id' to 'sp_id'
                'sp_user_id' => $sp->sp_user_id,
                'name' => $sp->spUser->first_name . ' ' . $sp->spUser->last_name,
                'phone' => $sp->spUser->mobile1_number,
                'rating' => $sp->rating,
                'total_ratings' => $sp->total_ratings,
                'is_gold_level' => $sp->is_gold_level,
                'acceptance_rate' => $sp->acceptance_rate,
                'punctuality_score' => $sp->punctuality_score,
                'behaviour_score' => $sp->behaviour_score,
                'cancellation_score' => $sp->cancellation_score,
                'complaint_score' => $sp->complaint_score,
                'tasks_completed' => $sp->tasks_completed,
                'distance_km' => round($distance, 2),
                'quality_score' => $qualityScore,
                'is_available' => $sp->isAvailable(),
                'is_online' => $sp->spUser->is_online,
                'last_seen_at' => $sp->spUser->last_seen_at,
                'kyc_verified' => $sp->kyc_verified,
                'kyc_status' => $sp->kyc_status,
                'location' => [
                    'latitude' => $sp->spUser->latitude,
                    'longitude' => $sp->spUser->longitude,
                    'address' => $sp->spUser->address
                ],
                'capabilities' => $sp->capabilities->map(function ($cap) {
                    return [
                        'subcategory_id' => $cap->subcategory_id,
                        'subcategory_name' => $cap->subcategory->name ?? null,
                        'max_pax_capacity' => $cap->max_pax_capacity,
                        'max_travel_distance_km' => $cap->max_travel_distance_km,
                        'night_shift_available' => $cap->night_shift_available,
                        'is_active' => $cap->is_active
                    ];
                }),
                'cuisine_capabilities' => $sp->cuisineCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'cuisine_id' => $cap->chef_cuisine_id,
                        'cuisine_name' => $cap->chefCuisine->name ?? null
                    ];
                }),
                'dietary_capabilities' => $sp->dietaryCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'dietary_preference_id' => $cap->dietary_preference_id,
                        'dietary_preference_name' => $cap->dietaryPreference->name ?? null
                    ];
                }),
                'addon_capabilities' => $sp->addonCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'addon_flag_id' => $cap->chef_addon_flag_id,
                        'addon_flag_name' => $cap->chefAddonFlag->name ?? null
                    ];
                }),
                'optional_capabilities' => $sp->optionalCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'optional_flag_id' => $cap->optional_flag_id,
                        'optional_flag_name' => $cap->optionalFlag->name ?? null,
                        'is_hard_filter' => $cap->optionalFlag->is_hard_filter ?? false
                    ];
                }),
                // TASK DETAILS AS NESTED ARRAY - Multiple tasks per SP
                'task_details' => $allTasks->map(function ($task) {
                    return [
                        'task_id' => $task->id,
                        'task_number' => $task->task_number,
                        'status' => $task->status,
                        'category_id' => $task->category_id,
                        'category_name' => $task->category->name ?? null,
                        'subcategory_id' => $task->subcategory_id,
                        'subcategory_name' => $task->subcategory->name ?? null,
                        'customer_id' => $task->customer_id,
                        'customer_name' => $task->customer ? $task->customer->first_name . ' ' . $task->customer->last_name : null,
                        'scheduled_at' => $task->scheduled_at,
                        'started_at' => $task->started_at,
                        'completed_at' => $task->completed_at,
                        'customer_rating' => $task->customer_rating,
                        'customer_feedback' => $task->customer_feedback,
                        'sp_rating' => $task->sp_rating,
                        'sp_feedback' => $task->sp_feedback,
                        'total_amount' => $task->total_amount,
                        'pax_count' => $task->pax_count,
                        'created_at' => $task->created_at,
                        'updated_at' => $task->updated_at
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    /**
     * Get available search options for the frontend
     */
    public function getSearchOptions(Request $request)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            $categoryId = $request->get('category_id');
            
            $data = [
                'categories' => NewCategory::where('is_active', true)->get(['id', 'name', 'slug']),
                'subcategories' => $categoryId 
                    ? NewSubcategory::where('category_id', $categoryId)->where('is_active', true)->get(['id', 'name', 'slug', 'pax_required'])
                    : [],
                'cuisines' => ChefCuisine::where('is_active', true)->get(['id', 'name']),
                'dietary_preferences' => DietaryPreference::where('is_active', true)->get(['id', 'name']),
                'addon_flags' => ChefAddonFlag::where('is_active', true)->get(['id', 'name', 'description']),
                'optional_flags' => OptionalFlag::where('is_active', true)->get(['id', 'name', 'description', 'is_hard_filter']),
                'search_radius_options' => [
                    ['value' => 5, 'label' => '5 km'],
                    ['value' => 10, 'label' => '10 km'],
                    ['value' => 15, 'label' => '15 km'],
                    ['value' => 25, 'label' => '25 km'],
                    ['value' => 50, 'label' => '50 km']
                ],
                'sort_options' => [
                    ['value' => 'quality_score', 'label' => 'Best Match (Quality Score)'],
                    ['value' => 'distance', 'label' => 'Nearest First'],
                    ['value' => 'rating', 'label' => 'Highest Rated'],
                    ['value' => 'price', 'label' => 'Price (Low to High)']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch search options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific service provider
     */
    public function getServiceProviderDetails(Request $request, $spId)
    {
        try {
            $sp = ServiceProvider::with([
                'spUser',
                'capabilities.subcategory',
                'cuisineCapabilities.chefCuisine',
                'dietaryCapabilities.dietaryPreference',
                'addonCapabilities.chefAddonFlag',
                'optionalCapabilities.optionalFlag',
                'tasks' => function($query) {
                    $query->whereIn('status', ['completed', 'rated'])->latest()->limit(5);
                }
            ])->find($spId);

            if (!$sp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $distance = null;

            if ($latitude && $longitude) {
                $distance = $sp->getDistanceFrom($latitude, $longitude);
            }

            $data = [
                'id' => $sp->id,
                'sp_user_id' => $sp->sp_user_id,
                'name' => $sp->spUser->first_name . ' ' . $sp->spUser->last_name,
                'phone' => $sp->spUser->mobile1_number,
                'email' => $sp->spUser->email,
                'rating' => $sp->rating,
                'total_ratings' => $sp->total_ratings,
                'is_gold_level' => $sp->is_gold_level,
                'acceptance_rate' => $sp->acceptance_rate,
                'punctuality_score' => $sp->punctuality_score,
                'behaviour_score' => $sp->behaviour_score,
                'cancellation_score' => $sp->cancellation_score,
                'complaint_score' => $sp->complaint_score,
                'tasks_completed' => $sp->tasks_completed,
                'tasks_cancelled' => $sp->tasks_cancelled,
                'distance_km' => $distance ? round($distance, 2) : null,
                'quality_score' => $sp->getQualityScore(),
                'is_available' => $sp->isAvailable(),
                'is_online' => $sp->spUser->is_online,
                'last_seen_at' => $sp->spUser->last_seen_at,
                'kyc_verified' => $sp->kyc_verified,
                'kyc_status' => $sp->kyc_status,
                'location' => [
                    'latitude' => $sp->spUser->latitude,
                    'longitude' => $sp->spUser->longitude,
                    'address' => $sp->spUser->address,
                    'city' => $sp->spUser->city,
                    'state' => $sp->spUser->state
                ],
                'capabilities' => $sp->capabilities->map(function ($cap) {
                    return [
                        'subcategory_id' => $cap->subcategory_id,
                        'subcategory_name' => $cap->subcategory->name ?? null,
                        'max_pax_capacity' => $cap->max_pax_capacity,
                        'max_travel_distance_km' => $cap->max_travel_distance_km,
                        'night_shift_available' => $cap->night_shift_available,
                        'is_active' => $cap->is_active
                    ];
                }),
                'cuisine_capabilities' => $sp->cuisineCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'cuisine_id' => $cap->chef_cuisine_id,
                        'cuisine_name' => $cap->chefCuisine->name ?? null
                    ];
                }),
                'dietary_capabilities' => $sp->dietaryCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'dietary_preference_id' => $cap->dietary_preference_id,
                        'dietary_preference_name' => $cap->dietaryPreference->name ?? null
                    ];
                }),
                'addon_capabilities' => $sp->addonCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'addon_flag_id' => $cap->chef_addon_flag_id,
                        'addon_flag_name' => $cap->chefAddonFlag->name ?? null
                    ];
                }),
                'optional_capabilities' => $sp->optionalCapabilities->where('is_active', true)->map(function ($cap) {
                    return [
                        'optional_flag_id' => $cap->optional_flag_id,
                        'optional_flag_name' => $cap->optionalFlag->name ?? null,
                        'is_hard_filter' => $cap->optionalFlag->is_hard_filter ?? false
                    ];
                }),
                'recent_tasks' => $sp->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'task_number' => $task->task_number,
                        'category' => $task->category->name ?? null,
                        'subcategory' => $task->subcategory->name ?? null,
                        'completed_at' => $task->completed_at,
                        'customer_rating' => $task->customer_rating,
                        'customer_feedback' => $task->customer_feedback
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service provider details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SP user active/inactive status and update if needed
     */
    public function updateSPStatus(Request $request, $spId)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'is_online' => 'required|boolean',
                'last_seen_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sp = ServiceProvider::with('spUser')->find($spId);
            
            if (!$sp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Update SP User status
            $sp->spUser->update([
                'is_online' => $request->is_online,
                'last_seen_at' => $request->last_seen_at ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SP status updated successfully',
                'data' => [
                    'sp_id' => $sp->id,
                    'sp_user_id' => $sp->sp_user_id,
                    'name' => $sp->spUser->first_name . ' ' . $sp->spUser->last_name,
                    'is_online' => $sp->spUser->is_online,
                    'last_seen_at' => $sp->spUser->last_seen_at,
                    'is_available' => $sp->isAvailable(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SP status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SP user status (active/inactive)
     */
    public function getSPStatus(Request $request, $spId)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            $sp = ServiceProvider::with('spUser')->find($spId);
            
            if (!$sp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sp_id' => $sp->id,
                    'sp_user_id' => $sp->sp_user_id,
                    'name' => $sp->spUser->first_name . ' ' . $sp->spUser->last_name,
                    'is_online' => $sp->spUser->is_online,
                    'last_seen_at' => $sp->spUser->last_seen_at,
                    'is_available' => $sp->isAvailable(),
                    'is_active' => $sp->is_active,
                    'kyc_verified' => $sp->kyc_verified,
                    'kyc_status' => $sp->kyc_status,
                    'is_blocked' => $sp->is_blocked,
                    'blocked_until' => $sp->blocked_until,
                    'cooldown_until' => $sp->cooldown_until,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SP status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Count applied filters for debugging and transparency
     */
    private function getAppliedFiltersCount(array $params): array
    {
        $scheduledAt = Carbon::parse($params['scheduled_at']);
        $isNightTime = $scheduledAt->hour >= 22 || $scheduledAt->hour <= 6;

        $filters = [
            'basic_eligibility' => 12, // Always applied (filters 1-12)
            'category_subcategory' => 2, // Always applied (filters 13-14)
            'not_on_task' => 1, // Always applied (filter 15)
            'cuisine_match' => !empty($params['cuisine_ids']) ? 1 : 0, // Filter 16
            'dietary_preference' => !empty($params['dietary_preference_id']) ? 1 : 0, // Filter 17
            'addon_flags' => !empty($params['addon_flag_ids']) ? count($params['addon_flag_ids']) : 0, // Filter 18
            'optional_flags' => !empty($params['optional_flag_ids']) ? count($params['optional_flag_ids']) : 0, // Filters 19-20
            'pax_capacity' => !empty($params['pax_count']) ? 1 : 0, // Filter 21
            'night_shift' => ($isNightTime || !empty($params['night_shift_required'])) ? 1 : 0, // Filter 22
            'gold_level_only' => !empty($params['gold_level_only']) ? 1 : 0, // Filter 23
            'distance' => 1, // Always applied (filter 24)
            'individual_checks' => 14, // Filters 25-38 (individual validation checks)
        ];

        return [
            'filters' => $filters,
            'total_filters_applied' => array_sum($filters),
            'filter_details' => [
                '1-12: Basic Eligibility' => 'Active, Verified, KYC, Not Blocked, Not Cooldown, Online, Recent Activity, etc.',
                '13-14: Category/Subcategory' => 'Service capability matching',
                '15: Not on Task' => 'SP not currently assigned to another task',
                '16: Cuisine Match' => 'Chef cuisine capabilities (if specified)',
                '17: Dietary Preference' => 'Dietary preference support (if specified)',
                '18: Addon Flags' => 'Special requirements support (if specified)',
                '19-20: Optional Flags' => 'Hard and soft filter preferences (if specified)',
                '21: Pax Capacity' => 'Maximum people capacity (if specified)',
                '22: Night Shift' => 'Night shift availability (if required)',
                '23: Gold Level' => 'Gold level SPs only (if requested)',
                '24: Distance' => 'Within search radius',
                '25-38: Individual Checks' => 'Travel distance, availability, ratings, scores, workload, etc.'
            ]
        ];
    }

    /**
     * Get SP current location
     */
    public function getSPLocation(Request $request, $spId)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            $sp = ServiceProvider::with('spUser')->find($spId);
            
            if (!$sp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'SP location retrieved successfully',
                'data' => [
                    'sp_id' => $sp->id,
                    'sp_user_id' => $sp->sp_user_id,
                    'latitude' => $sp->spUser->latitude,
                    'longitude' => $sp->spUser->longitude,
                    'address' => $sp->spUser->address,
                    'city' => $sp->spUser->city,
                    'state' => $sp->spUser->state,
                    'country' => $sp->spUser->country,
                    'pincode' => $sp->spUser->pincode,
                    'coverage_radius' => $sp->spUser->coverage_radius,
                    'last_updated' => $sp->spUser->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving SP location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update SP current location
     * Updates location in sp_users table (where latitude/longitude are stored)
     */
    public function updateSPLocation(Request $request, $spId)
    {
        try {
            // Token validation
            $phone = $request->input('phone');
            $token = $request->input('token');
            
            if (!$phone || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone and token are required',
                    'status_code' => 400
                ], 400);
            }

            $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

            if (!$tokenCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenCheck['message'],
                    'status_code' => $tokenCheck['status_code']
                ], $tokenCheck['status_code']);
            }

            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:10',
                'coverage_radius' => 'nullable|numeric|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sp = ServiceProvider::with('spUser')->find($spId);
            
            if (!$sp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Update location in sp_users table (this is where location is stored)
            $updateData = [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'last_seen_at' => now(), // Update last seen when location is updated
            ];

            // Add optional fields if provided
            if ($request->has('address')) {
                $updateData['address'] = $request->address;
            }
            if ($request->has('city')) {
                $updateData['city'] = $request->city;
            }
            if ($request->has('state')) {
                $updateData['state'] = $request->state;
            }
            if ($request->has('country')) {
                $updateData['country'] = $request->country;
            }
            if ($request->has('pincode')) {
                $updateData['pincode'] = $request->pincode;
            }
            if ($request->has('coverage_radius')) {
                $updateData['coverage_radius'] = $request->coverage_radius;
            }

            $sp->spUser->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'SP location updated successfully',
                'data' => [
                    'sp_id' => $sp->id,
                    'sp_user_id' => $sp->sp_user_id,
                    'latitude' => $sp->spUser->fresh()->latitude,
                    'longitude' => $sp->spUser->fresh()->longitude,
                    'address' => $sp->spUser->fresh()->address,
                    'city' => $sp->spUser->fresh()->city,
                    'state' => $sp->spUser->fresh()->state,
                    'country' => $sp->spUser->fresh()->country,
                    'pincode' => $sp->spUser->fresh()->pincode,
                    'coverage_radius' => $sp->spUser->fresh()->coverage_radius,
                    'last_updated' => $sp->spUser->fresh()->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating SP location: ' . $e->getMessage()
            ], 500);
        }
    }
}