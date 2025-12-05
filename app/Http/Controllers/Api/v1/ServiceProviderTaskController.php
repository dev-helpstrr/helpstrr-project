<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\SPUser;
use App\Models\TaskBroadcast;
use App\Models\SpLocationTracking;
use App\Models\SPCapability;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\TaskAssignmentLog;
use App\Models\CustomerAddress;
use App\Services\NotificationService;
use App\Services\TaskAllocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServiceProviderTaskController extends Controller
{
    protected $notificationService;
    protected $taskAllocationService;

    public function __construct(
        NotificationService $notificationService,
        TaskAllocationService $taskAllocationService
    ) {
        $this->notificationService = $notificationService;
        $this->taskAllocationService = $taskAllocationService;
    }

    /**
     * Get service provider dashboard
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spId = $request->input('sp_id');
            $serviceProvider = ServiceProvider::with('spUser')->find($spId);

            if (!$serviceProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Get today's tasks
            $todaysTasks = Task::where('service_provider_id', $spId)
                ->whereDate('scheduled_at', today())
                ->with(['customer', 'category', 'subcategory', 'service', 'customerAddress'])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            // Get current active task
            $activeTask = Task::where('service_provider_id', $spId)
                ->whereIn('status', [
                    Task::STATUS_ASSIGNED,
                    Task::STATUS_ON_THE_WAY,
                    Task::STATUS_ARRIVED,
                    Task::STATUS_OTP_START_VERIFIED,
                    Task::STATUS_STARTED,
                    Task::STATUS_PAUSED,
                    Task::STATUS_RESUMED,
                ])
                ->with(['customer', 'category', 'subcategory', 'service', 'customerAddress'])
                ->first();

            // Get pending task requests
            $pendingRequests = TaskBroadcast::where('service_provider_id', $spId)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with(['task.customer', 'task.category', 'task.subcategory', 'task.service', 'task.customerAddress'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate earnings
            $earnings = $this->calculateEarnings($spId);

            // Get performance metrics
            $metrics = $this->getPerformanceMetrics($serviceProvider);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'service_provider' => [
                        'id' => $serviceProvider->id,
                        'name' => $serviceProvider->spUser->name,
                        'phone' => $serviceProvider->spUser->phone,
                        'rating' => $serviceProvider->rating,
                        'total_ratings' => $serviceProvider->total_ratings,
                        'is_online' => $serviceProvider->spUser->is_online,
                        'is_available' => $serviceProvider->isAvailable(),
                        'kyc_verified' => $serviceProvider->kyc_verified,
                        'is_gold_level' => $serviceProvider->is_gold_level,
                    ],
                    'active_task' => $activeTask ? $this->formatTaskResponse($activeTask) : null,
                    'todays_tasks' => $todaysTasks->map(function ($task) {
                        return $this->formatTaskResponse($task);
                    }),
                    'pending_requests' => $pendingRequests->map(function ($broadcast) {
                        return $this->formatTaskRequestResponse($broadcast);
                    }),
                    'earnings' => $earnings,
                    'metrics' => $metrics,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get assigned tasks for service provider
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAssignedTasks(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'status' => 'nullable|string|in:all,active,completed,upcoming',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $spId = $data['sp_id'];
            $status = $data['status'] ?? 'all';
            $page = $data['page'] ?? 1;
            $perPage = $data['per_page'] ?? 10;

            // Build query
            $query = Task::with([
                'customer',
                'customerAddress',
                'category',
                'subcategory',
                'service',
                'priceComponents'
            ])
            ->where('service_provider_id', $spId)
            ->where('is_active', true);

            // Apply status filter
            switch ($status) {
                case 'active':
                    $query->whereIn('status', [
                        Task::STATUS_ASSIGNED,
                        Task::STATUS_ON_THE_WAY,
                        Task::STATUS_ARRIVED,
                        Task::STATUS_OTP_START_VERIFIED,
                        Task::STATUS_STARTED,
                        Task::STATUS_PAUSED,
                        Task::STATUS_RESUMED,
                    ]);
                    break;
                case 'completed':
                    $query->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_RATED]);
                    break;
                case 'upcoming':
                    $query->whereIn('status', [Task::STATUS_ASSIGNED])
                        ->where('scheduled_at', '>', now());
                    break;
                // 'all' - no additional filter
            }

            // Apply date filters
            if (!empty($data['date_from'])) {
                $query->whereDate('scheduled_at', '>=', $data['date_from']);
            }
            if (!empty($data['date_to'])) {
                $query->whereDate('scheduled_at', '<=', $data['date_to']);
            }

            // Order by scheduled time
            $query->orderBy('scheduled_at', 'desc');

            // Paginate results
            $tasks = $query->paginate($perPage, ['*'], 'page', $page);

            // Format response
            $formattedTasks = $tasks->getCollection()->map(function ($task) {
                return $this->formatTaskResponse($task);
            });

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => [
                    'tasks' => $formattedTasks,
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'last_page' => $tasks->lastPage(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Accept a task request
     * 
     * @param Request $request
     * @param int $broadcastId
     * @return JsonResponse
     */
    public function acceptTaskRequest(Request $request, int $broadcastId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $spId = $data['sp_id'];

            $broadcast = TaskBroadcast::with('task')
                ->where('id', $broadcastId)
                ->where('service_provider_id', $spId)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if (!$broadcast) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task request not found or expired'
                ], 404);
            }

            $task = $broadcast->task;

            // Check if task is still available
            if ($task->status !== Task::STATUS_SEARCHING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task is no longer available'
                ], 400);
            }

            // Check if service provider is available
            $serviceProvider = ServiceProvider::find($spId);
            if (!$serviceProvider->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not available to accept tasks'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Accept the broadcast
                $broadcast->update([
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);

                // Assign task to service provider
                $task->update([
                    'service_provider_id' => $spId,
                    'status' => Task::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                ]);

                // Reject all other pending broadcasts for this task
                TaskBroadcast::where('task_id', $task->id)
                    ->where('id', '!=', $broadcastId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'auto_rejected',
                        'responded_at' => now(),
                    ]);

                // Update service provider metrics
                $this->updateAcceptanceMetrics($serviceProvider);

                // Update location if provided
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $this->updateServiceProviderLocation($spId, $data['latitude'], $data['longitude']);
                }

                // Send notifications
                $this->notificationService->sendTaskAssignmentNotification($task);

                DB::commit();

                // Load fresh task data
                $task->load([
                    'customer',
                    'customerAddress',
                    'category',
                    'subcategory',
                    'service',
                    'priceComponents'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task accepted successfully',
                    'data' => [
                        'task' => $this->formatTaskResponse($task),
                        'next_actions' => $this->getServiceProviderActions($task),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reject a task request
     * 
     * @param Request $request
     * @param int $broadcastId
     * @return JsonResponse
     */
    public function rejectTaskRequest(Request $request, int $broadcastId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $spId = $data['sp_id'];

            $broadcast = TaskBroadcast::where('id', $broadcastId)
                ->where('service_provider_id', $spId)
                ->where('status', 'pending')
                ->first();

            if (!$broadcast) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task request not found'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Reject the broadcast
                $broadcast->update([
                    'status' => 'rejected',
                    'responded_at' => now(),
                    'rejection_reason' => $data['reason'] ?? null,
                ]);

                // Update service provider metrics
                $serviceProvider = ServiceProvider::find($spId);
                $this->updateRejectionMetrics($serviceProvider);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task rejected successfully'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get earnings summary
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getEarnings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'period' => 'nullable|string|in:today,week,month,year',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spId = $request->input('sp_id');
            $period = $request->input('period', 'month');

            $earnings = $this->getDetailedEarnings($spId, $period);

            return response()->json([
                'success' => true,
                'message' => 'Earnings retrieved successfully',
                'data' => $earnings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve earnings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update availability status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_user_id' => 'required|exists:s_p_users,id',
                'is_online' => 'required|boolean',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $spUserId = $data['sp_user_id'];

            $spUser = SPUser::find($spUserId);
            if (!$spUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider user not found'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Update online status
                $spUser->update([
                    'is_online' => $data['is_online'],
                    'last_seen' => now(),
                ]);

                // Update location if provided
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $spUser->update([
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Availability updated successfully',
                    'data' => [
                        'is_online' => $spUser->is_online,
                        'last_seen' => $spUser->last_seen->toISOString(),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update availability',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Format task response for service provider
     */
    private function formatTaskResponse(Task $task): array
    {
        return [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'status' => $task->status,
            'status_badge' => $task->status_badge,
            'customer' => [
                'id' => $task->customer->id,
                'name' => $task->customer->name,
                'phone' => $task->customer->phone,
            ],
            'address' => [
                'address_line_1' => $task->customerAddress->address_line_1,
                'address_line_2' => $task->customerAddress->address_line_2,
                'city' => $task->customerAddress->city,
                'pincode' => $task->customerAddress->pincode,
                'latitude' => $task->customerAddress->latitude,
                'longitude' => $task->customerAddress->longitude,
            ],
            'service' => [
                'category' => $task->category->name,
                'subcategory' => $task->subcategory->name,
                'service' => $task->service->name,
            ],
            'details' => [
                'pax_count' => $task->pax_count,
                'requested_hours' => $task->requested_hours,
                'billable_hours' => $task->billable_hours,
                'special_instructions' => $task->special_instructions,
            ],
            'schedule' => [
                'scheduled_at' => $task->scheduled_at->toISOString(),
                'start_time' => $task->start_time->format('H:i'),
                'end_time' => $task->end_time->format('H:i'),
                'assigned_at' => $task->assigned_at?->toISOString(),
                'started_at' => $task->started_at?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
            ],
            'earnings' => [
                'total_amount' => $task->total_amount,
                'sp_payout' => $this->calculateServiceProviderPayout($task),
            ],
            'otp' => [
                'start_otp' => $task->start_otp,
                'end_otp' => $task->end_otp,
            ],
            'ratings' => [
                'sp_rating' => $task->sp_rating,
                'sp_feedback' => $task->sp_feedback,
                'customer_rating' => $task->customer_rating,
                'can_rate' => $task->status === Task::STATUS_COMPLETED && !$task->sp_rating,
            ],
        ];
    }

    /**
     * Format task request response
     */
    private function formatTaskRequestResponse(TaskBroadcast $broadcast): array
    {
        $task = $broadcast->task;
        
        return [
            'broadcast_id' => $broadcast->id,
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'customer' => [
                'name' => $task->customer->name,
                'phone' => substr($task->customer->phone, 0, 6) . 'XXXX', // Masked phone
            ],
            'address' => [
                'area' => $task->customerAddress->city,
                'pincode' => $task->customerAddress->pincode,
                'distance_km' => $this->calculateDistanceFromSP($broadcast->service_provider_id, $task->customerAddress),
            ],
            'service' => [
                'category' => $task->category->name,
                'subcategory' => $task->subcategory->name,
                'service' => $task->service->name,
            ],
            'details' => [
                'pax_count' => $task->pax_count,
                'requested_hours' => $task->requested_hours,
                'special_instructions' => $task->special_instructions,
            ],
            'schedule' => [
                'scheduled_at' => $task->scheduled_at->toISOString(),
                'start_time' => $task->start_time->format('H:i'),
                'end_time' => $task->end_time->format('H:i'),
            ],
            'earnings' => [
                'estimated_payout' => $this->calculateServiceProviderPayout($task),
            ],
            'expires_at' => $broadcast->expires_at->toISOString(),
            'time_remaining' => $broadcast->expires_at->diffInSeconds(now()),
        ];
    }

    /**
     * Get available actions for service provider
     */
    private function getServiceProviderActions(Task $task): array
    {
        $actions = [];

        switch ($task->status) {
            case Task::STATUS_ASSIGNED:
                $actions[] = ['action' => 'start_journey', 'label' => 'Start Journey'];
                $actions[] = ['action' => 'cancel_task', 'label' => 'Cancel Task'];
                break;

            case Task::STATUS_ON_THE_WAY:
                $actions[] = ['action' => 'mark_arrived', 'label' => 'Mark Arrived'];
                break;

            case Task::STATUS_ARRIVED:
                $actions[] = ['action' => 'request_start_otp', 'label' => 'Request Start OTP'];
                break;

            case Task::STATUS_OTP_START_VERIFIED:
                $actions[] = ['action' => 'start_task', 'label' => 'Start Task'];
                break;

            case Task::STATUS_STARTED:
                $actions[] = ['action' => 'pause_task', 'label' => 'Pause Task'];
                $actions[] = ['action' => 'complete_task', 'label' => 'Complete Task'];
                break;

            case Task::STATUS_PAUSED:
                $actions[] = ['action' => 'resume_task', 'label' => 'Resume Task'];
                break;

            case Task::STATUS_COMPLETED:
                if (!$task->sp_rating) {
                    $actions[] = ['action' => 'rate_customer', 'label' => 'Rate Customer'];
                }
                break;
        }

        return $actions;
    }

    /**
     * Calculate earnings for different periods
     */
    private function calculateEarnings(int $spId): array
    {
        $baseQuery = Task::where('service_provider_id', $spId)
            ->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_RATED]);

        return [
            'today' => $baseQuery->whereDate('completed_at', today())->sum('total_amount') * 0.8, // 80% to SP
            'this_week' => $baseQuery->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount') * 0.8,
            'this_month' => $baseQuery->whereMonth('completed_at', now()->month)->sum('total_amount') * 0.8,
            'total' => $baseQuery->sum('total_amount') * 0.8,
        ];
    }

    /**
     * Get detailed earnings for a specific period
     */
    private function getDetailedEarnings(int $spId, string $period): array
    {
        $startDate = match($period) {
            'today' => today(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $endDate = match($period) {
            'today' => today()->endOfDay(),
            'week' => now()->endOfWeek(),
            'month' => now()->endOfMonth(),
            'year' => now()->endOfYear(),
            default => now()->endOfMonth(),
        };

        $completedTasks = Task::where('service_provider_id', $spId)
            ->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_RATED])
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['category', 'priceComponents'])
            ->get();

        $totalEarnings = $completedTasks->sum('total_amount') * 0.8; // 80% to SP
        $totalTasks = $completedTasks->count();
        $totalHours = $completedTasks->sum('billable_hours');

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_earnings' => $totalEarnings,
            'total_tasks' => $totalTasks,
            'total_hours' => $totalHours,
            'average_per_task' => $totalTasks > 0 ? $totalEarnings / $totalTasks : 0,
            'average_per_hour' => $totalHours > 0 ? $totalEarnings / $totalHours : 0,
            'earnings_by_category' => $completedTasks->groupBy('category.name')->map(function ($tasks, $category) {
                return [
                    'category' => $category,
                    'tasks' => $tasks->count(),
                    'earnings' => $tasks->sum('total_amount') * 0.8,
                ];
            })->values(),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(ServiceProvider $serviceProvider): array
    {
        return [
            'rating' => $serviceProvider->rating,
            'total_ratings' => $serviceProvider->total_ratings,
            'acceptance_rate' => $serviceProvider->acceptance_rate,
            'punctuality_score' => $serviceProvider->punctuality_score,
            'behaviour_score' => $serviceProvider->behaviour_score,
            'cancellation_score' => $serviceProvider->cancellation_score,
            'tasks_completed' => $serviceProvider->tasks_completed,
            'tasks_cancelled' => $serviceProvider->tasks_cancelled,
            'quality_score' => $serviceProvider->getQualityScore(),
            'is_gold_level' => $serviceProvider->is_gold_level,
        ];
    }

    /**
     * Helper methods
     */
    private function calculateServiceProviderPayout(Task $task): float
    {
        return $task->total_amount * 0.8; // 80% to service provider, 20% platform commission
    }

    private function calculateDistanceFromSP(int $spId, $customerAddress): float
    {
        $serviceProvider = ServiceProvider::with('spUser')->find($spId);
        if (!$serviceProvider || !$serviceProvider->spUser->latitude || !$serviceProvider->spUser->longitude) {
            return 0;
        }

        return $serviceProvider->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude);
    }

    private function updateAcceptanceMetrics(ServiceProvider $serviceProvider): void
    {
        $totalBroadcasts = TaskBroadcast::where('service_provider_id', $serviceProvider->id)->count();
        $acceptedBroadcasts = TaskBroadcast::where('service_provider_id', $serviceProvider->id)
            ->where('status', 'accepted')->count();

        if ($totalBroadcasts > 0) {
            $acceptanceRate = ($acceptedBroadcasts / $totalBroadcasts) * 100;
            $serviceProvider->update(['acceptance_rate' => $acceptanceRate]);
        }
    }

    private function updateRejectionMetrics(ServiceProvider $serviceProvider): void
    {
        $serviceProvider->increment('tasks_rejected');
        
        // Update rejection frequency
        $recentRejections = TaskBroadcast::where('service_provider_id', $serviceProvider->id)
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $serviceProvider->update(['rejection_frequency' => $recentRejections]);

        // Apply cooldown if too many rejections
        if ($recentRejections >= 5) {
            $serviceProvider->setCooldown(60); // 1 hour cooldown
        }
    }

    private function updateServiceProviderLocation(int $spId, float $latitude, float $longitude): void
    {
        $serviceProvider = ServiceProvider::with('spUser')->find($spId);
        if ($serviceProvider && $serviceProvider->spUser) {
            $serviceProvider->spUser->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'last_location_update' => now(),
            ]);
        }
    }

    /**
     * Search and filter service providers based on criteria
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchProviders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'max_distance_km' => 'nullable|numeric|min:1|max:100',
                'min_rating' => 'nullable|numeric|min:1|max:5',
                'max_hourly_rate' => 'nullable|numeric|min:0',
                'availability_date' => 'nullable|date|after_or_equal:today',
                'availability_time' => 'nullable|date_format:H:i',
                'pax_count' => 'nullable|integer|min:1|max:50',
                'required_hours' => 'nullable|integer|min:1|max:24',
                'is_online_only' => 'nullable|boolean',
                'has_vehicle' => 'nullable|boolean',
                'experience_years' => 'nullable|integer|min:0|max:50',
                'certification_level' => 'nullable|string|in:basic,intermediate,advanced,expert',
                'equipment_provided' => 'nullable|boolean',
                'materials_provided' => 'nullable|boolean',
                'insurance_covered' => 'nullable|boolean',
                'background_verified' => 'nullable|boolean',
                'language_skills' => 'nullable|array',
                'special_skills' => 'nullable|array',
                'night_shift_available' => 'nullable|boolean',
                'weekend_available' => 'nullable|boolean',
                'emergency_available' => 'nullable|boolean',
                'sort_by' => 'nullable|string|in:distance,rating,price,experience,availability',
                'sort_order' => 'nullable|string|in:asc,desc',
                'limit' => 'nullable|integer|min:1|max:100',
                'offset' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            
            // Build the query
            $query = ServiceProvider::query()
                ->with(['spUser', 'capabilities', 'locationTracking'])
                ->where('is_active', true)
                ->where('kyc_verified', true);

            // Apply capability-based filters
            if (!empty($filters['category_id']) || !empty($filters['subcategory_id'])) {
                $query->whereHas('capabilities', function ($q) use ($filters) {
                    $q->where('is_active', true);
                    
                    if (!empty($filters['category_id'])) {
                        $q->where('category_id', $filters['category_id']);
                    }
                    
                    if (!empty($filters['subcategory_id'])) {
                        $q->where('subcategory_id', $filters['subcategory_id']);
                    }

                    // Apply capability filters
                    if (!empty($filters['max_hourly_rate'])) {
                        $q->where('hourly_rate', '<=', $filters['max_hourly_rate']);
                    }
                    
                    if (!empty($filters['pax_count'])) {
                        $q->where('max_pax_capacity', '>=', $filters['pax_count']);
                    }
                    
                    if (!empty($filters['required_hours'])) {
                        $q->where('max_hours', '>=', $filters['required_hours']);
                    }

                    if (isset($filters['night_shift_available'])) {
                        $q->where('night_shift_available', $filters['night_shift_available']);
                    }

                    if (isset($filters['weekend_available'])) {
                        $q->where('weekend_available', $filters['weekend_available']);
                    }

                    if (isset($filters['emergency_available'])) {
                        $q->where('emergency_available', $filters['emergency_available']);
                    }

                    if (!empty($filters['experience_years'])) {
                        $q->where('experience_years', '>=', $filters['experience_years']);
                    }

                    if (!empty($filters['certification_level'])) {
                        $q->where('certification_level', $filters['certification_level']);
                    }

                    if (isset($filters['equipment_provided'])) {
                        $q->where('equipment_provided', $filters['equipment_provided']);
                    }

                    if (isset($filters['materials_provided'])) {
                        $q->where('materials_provided', $filters['materials_provided']);
                    }

                    if (isset($filters['insurance_covered'])) {
                        $q->where('insurance_covered', $filters['insurance_covered']);
                    }

                    if (isset($filters['background_verified'])) {
                        $q->where('background_verified', $filters['background_verified']);
                    }
                });
            }

            // Apply rating filter
            if (!empty($filters['min_rating'])) {
                $query->where('rating', '>=', $filters['min_rating']);
            }

            // Apply online status filter
            if (isset($filters['is_online_only']) && $filters['is_online_only']) {
                $query->whereHas('spUser', function ($q) {
                    $q->where('is_online', true)
                      ->where('last_seen_at', '>=', now()->subMinutes(30));
                });
            }

            // Apply location and distance filters
            if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
                $lat = $filters['latitude'];
                $lng = $filters['longitude'];
                $maxDistance = $filters['max_distance_km'] ?? 25; // Default 25km

                $query->whereHas('spUser', function ($q) use ($lat, $lng, $maxDistance) {
                    $q->whereNotNull('latitude')
                      ->whereNotNull('longitude')
                      ->whereRaw("
                          (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                          cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                          sin(radians(latitude)))) <= ?
                      ", [$lat, $lng, $lat, $maxDistance]);
                });

                // Add distance calculation to select
                $query->selectRaw("
                    service_providers.*,
                    (6371 * acos(cos(radians(?)) * cos(radians(sp_users.latitude)) * 
                    cos(radians(sp_users.longitude) - radians(?)) + sin(radians(?)) * 
                    sin(radians(sp_users.latitude)))) as distance_km
                ", [$lat, $lng, $lat])
                ->join('sp_users', 'service_providers.sp_user_id', '=', 'sp_users.id');
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'rating';
            $sortOrder = $filters['sort_order'] ?? 'desc';

            switch ($sortBy) {
                case 'distance':
                    if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
                        $query->orderBy('distance_km', $sortOrder);
                    }
                    break;
                case 'rating':
                    $query->orderBy('rating', $sortOrder);
                    break;
                case 'price':
                    $query->orderBy('hourly_rate', $sortOrder);
                    break;
                case 'experience':
                    $query->orderBy('experience_years', $sortOrder);
                    break;
                default:
                    $query->orderBy('rating', 'desc');
            }

            // Apply pagination
            $limit = $filters['limit'] ?? 20;
            $offset = $filters['offset'] ?? 0;
            
            $total = $query->count();
            $providers = $query->skip($offset)->take($limit)->get();

            // Format response
            $formattedProviders = $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->spUser->name,
                    'phone' => $provider->spUser->phone,
                    'email' => $provider->spUser->email,
                    'rating' => $provider->rating,
                    'total_ratings' => $provider->total_ratings,
                    'tasks_completed' => $provider->tasks_completed,
                    'experience_years' => $provider->experience_years,
                    'hourly_rate' => $provider->hourly_rate,
                    'is_online' => $provider->spUser->is_online,
                    'last_seen_at' => $provider->spUser->last_seen_at?->toISOString(),
                    'distance_km' => $provider->distance_km ?? null,
                    'location' => [
                        'latitude' => $provider->spUser->latitude,
                        'longitude' => $provider->spUser->longitude,
                    ],
                    'capabilities' => $provider->capabilities->map(function ($cap) {
                        return [
                            'category' => $cap->category->name,
                            'subcategory' => $cap->subcategory->name,
                            'hourly_rate' => $cap->hourly_rate,
                            'max_pax_capacity' => $cap->max_pax_capacity,
                            'experience_years' => $cap->experience_years,
                            'certification_level' => $cap->certification_level,
                        ];
                    }),
                    'verification' => [
                        'kyc_verified' => $provider->kyc_verified,
                        'background_verified' => $provider->background_verified,
                        'insurance_covered' => $provider->insurance_covered,
                    ],
                    'availability' => [
                        'night_shift' => $provider->night_shift_available,
                        'weekend' => $provider->weekend_available,
                        'emergency' => $provider->emergency_available,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'providers' => $formattedProviders,
                    'pagination' => [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total,
                    ],
                    'filters_applied' => array_filter($filters),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search providers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get provider status and availability
     * 
     * @param int $providerId
     * @return JsonResponse
     */
    public function getProviderStatus(int $providerId): JsonResponse
    {
        try {
            $provider = ServiceProvider::with(['spUser', 'locationTracking' => function ($q) {
                $q->where('is_active', true)->latest();
            }])->find($providerId);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found'
                ], 404);
            }

            $latestLocation = $provider->locationTracking->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'provider_id' => $provider->id,
                    'name' => $provider->spUser->name,
                    'is_online' => $provider->spUser->is_online,
                    'is_active' => $provider->is_active,
                    'is_available' => $provider->is_available,
                    'last_seen_at' => $provider->spUser->last_seen_at?->toISOString(),
                    'current_location' => $latestLocation ? [
                        'latitude' => $latestLocation->latitude,
                        'longitude' => $latestLocation->longitude,
                        'accuracy' => $latestLocation->accuracy,
                        'updated_at' => $latestLocation->created_at->toISOString(),
                    ] : null,
                    'rating' => $provider->rating,
                    'tasks_completed' => $provider->tasks_completed,
                    'acceptance_rate' => $provider->acceptance_rate,
                    'current_task_count' => $provider->tasks()->whereIn('status', [
                        Task::STATUS_ASSIGNED,
                        Task::STATUS_ON_THE_WAY,
                        Task::STATUS_ARRIVED,
                        Task::STATUS_STARTED
                    ])->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get provider status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update provider location
     * 
     * @param Request $request
     * @param int $providerId
     * @return JsonResponse
     */
    public function updateProviderLocation(Request $request, int $providerId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
                'speed' => 'nullable|numeric|min:0',
                'heading' => 'nullable|numeric|min:0|max:360',
                'altitude' => 'nullable|numeric',
                'battery_level' => 'nullable|integer|min:0|max:100',
                'network_type' => 'nullable|string|in:2G,3G,4G,5G,WiFi',
                'app_version' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $provider = ServiceProvider::with('spUser')->find($providerId);
            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found'
                ], 404);
            }

            $data = $validator->validated();

            DB::beginTransaction();

            try {
                // Create location tracking entry
                SpLocationTracking::create([
                    'service_provider_id' => $providerId,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? 10,
                    'speed' => $data['speed'] ?? 0,
                    'heading' => $data['heading'] ?? 0,
                    'altitude' => $data['altitude'] ?? 0,
                    'is_active' => true,
                    'battery_level' => $data['battery_level'] ?? 100,
                    'network_type' => $data['network_type'] ?? '4G',
                    'app_version' => $data['app_version'] ?? '1.0.0',
                ]);

                // Update SP user location
                $provider->spUser->update([
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'last_seen_at' => now(),
                ]);

                // Mark previous location entries as inactive
                SpLocationTracking::where('service_provider_id', $providerId)
                    ->where('is_active', true)
                    ->where('id', '!=', SpLocationTracking::latest()->first()->id)
                    ->update(['is_active' => false]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully',
                    'data' => [
                        'provider_id' => $providerId,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'updated_at' => now()->toISOString(),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available tasks for service provider (filtered based on capabilities)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableTasks(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'max_distance_km' => 'nullable|numeric|min:1|max:100',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spId = $request->input('sp_id');
            $serviceProvider = ServiceProvider::with(['spUser', 'capabilities'])->find($spId);

            if (!$serviceProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not found'
                ], 404);
            }

            // Get filtered tasks based on SP capabilities
            $availableTasks = $this->getFilteredTasksForProvider($serviceProvider, $request->all());

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $availableTasks,
                    'provider_info' => [
                        'id' => $serviceProvider->id,
                        'name' => $serviceProvider->spUser->name,
                        'rating' => $serviceProvider->rating,
                        'is_online' => $serviceProvider->spUser->is_online,
                        'current_location' => [
                            'latitude' => $serviceProvider->spUser->latitude,
                            'longitude' => $serviceProvider->spUser->longitude,
                        ],
                    ],
                    'total_available' => count($availableTasks),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available tasks',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Accept a task with conflict resolution
     * 
     * @param Request $request
     * @param int $broadcastId
     * @return JsonResponse
     */
    public function acceptTaskWithConflictResolution(Request $request, int $broadcastId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'estimated_arrival_time' => 'nullable|integer|min:1|max:120', // minutes
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spId = $request->input('sp_id');
            $broadcast = TaskBroadcast::with(['task', 'serviceProvider'])->find($broadcastId);

            if (!$broadcast) {
                return response()->json([
                    'success' => false,
                    'message' => 'Broadcast not found'
                ], 404);
            }

            if ($broadcast->service_provider_id !== $spId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to accept this task'
                ], 403);
            }

            if ($broadcast->response !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task already responded to',
                    'current_response' => $broadcast->response
                ], 400);
            }

            if ($broadcast->expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task broadcast has expired'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $task = $broadcast->task;
                $responseTime = now()->diffInSeconds($broadcast->sent_at);
                
                // Calculate distance to customer
                $distance = null;
                if ($request->filled(['latitude', 'longitude']) && $task->customerAddress) {
                    $distance = $this->calculateDistance(
                        $request->input('latitude'),
                        $request->input('longitude'),
                        $task->customerAddress->latitude,
                        $task->customerAddress->longitude
                    );
                }

                // Log the acceptance
                $assignmentLog = TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $spId,
                    'action_type' => TaskAssignmentLog::ACTION_ACCEPTED,
                    'action_timestamp' => now(),
                    'response_time_seconds' => $responseTime,
                    'distance_km' => $distance,
                    'assignment_round' => $broadcast->broadcast_round,
                    'broadcast_id' => $broadcastId,
                    'metadata' => [
                        'estimated_arrival_time' => $request->input('estimated_arrival_time'),
                        'provider_location' => [
                            'latitude' => $request->input('latitude'),
                            'longitude' => $request->input('longitude'),
                        ],
                    ],
                ]);

                // Calculate priority score
                $priorityScore = $assignmentLog->calculatePriorityScore();
                $assignmentLog->update(['priority_score' => $priorityScore]);

                // Check for conflicts (other acceptances within 2 minutes)
                $conflictingAcceptances = TaskAssignmentLog::getConflictingAcceptances($task->id);

                if ($conflictingAcceptances->count() > 1) {
                    // Handle conflict resolution
                    $result = $this->resolveTaskAssignmentConflict($task, $conflictingAcceptances);
                    
                    if ($result['winner_sp_id'] === $spId) {
                        // This SP won the conflict resolution
                        $this->assignTaskToProvider($task, $broadcast, $spId);
                        
                        DB::commit();
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Task accepted successfully (conflict resolved)',
                            'data' => [
                                'task_id' => $task->id,
                                'task_number' => $task->task_number,
                                'assigned' => true,
                                'conflict_resolved' => true,
                                'priority_score' => $priorityScore,
                                'response_time_seconds' => $responseTime,
                                'distance_km' => $distance,
                                'estimated_arrival' => $request->input('estimated_arrival_time'),
                            ]
                        ]);
                    } else {
                        // This SP lost the conflict resolution
                        $broadcast->update([
                            'response' => 'conflict_lost',
                            'responded_at' => now(),
                            'response_time_seconds' => $responseTime,
                        ]);

                        DB::commit();

                        return response()->json([
                            'success' => false,
                            'message' => 'Task was assigned to another provider due to conflict resolution',
                            'data' => [
                                'task_id' => $task->id,
                                'conflict_resolved' => true,
                                'winner_sp_id' => $result['winner_sp_id'],
                                'your_priority_score' => $priorityScore,
                                'winner_priority_score' => $result['winner_score'],
                            ]
                        ], 409);
                    }
                } else {
                    // No conflict, assign directly
                    $this->assignTaskToProvider($task, $broadcast, $spId);
                    
                    DB::commit();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Task accepted and assigned successfully',
                        'data' => [
                            'task_id' => $task->id,
                            'task_number' => $task->task_number,
                            'assigned' => true,
                            'conflict_resolved' => false,
                            'priority_score' => $priorityScore,
                            'response_time_seconds' => $responseTime,
                            'distance_km' => $distance,
                            'estimated_arrival' => $request->input('estimated_arrival_time'),
                        ]
                    ]);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reject a task and trigger next round assignment
     * 
     * @param Request $request
     * @param int $broadcastId
     * @return JsonResponse
     */
    public function rejectTaskWithFallback(Request $request, int $broadcastId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sp_id' => 'required|exists:service_providers,id',
                'rejection_reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spId = $request->input('sp_id');
            $broadcast = TaskBroadcast::with(['task'])->find($broadcastId);

            if (!$broadcast) {
                return response()->json([
                    'success' => false,
                    'message' => 'Broadcast not found'
                ], 404);
            }

            if ($broadcast->service_provider_id !== $spId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reject this task'
                ], 403);
            }

            if ($broadcast->response !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task already responded to'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $task = $broadcast->task;
                $responseTime = now()->diffInSeconds($broadcast->sent_at);

                // Log the rejection
                TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $spId,
                    'action_type' => TaskAssignmentLog::ACTION_REJECTED,
                    'action_timestamp' => now(),
                    'response_time_seconds' => $responseTime,
                    'assignment_round' => $broadcast->broadcast_round,
                    'broadcast_id' => $broadcastId,
                    'rejection_reason' => $request->input('rejection_reason'),
                ]);

                // Update broadcast
                $broadcast->update([
                    'response' => 'rejected',
                    'responded_at' => now(),
                    'response_time_seconds' => $responseTime,
                    'rejection_reason' => $request->input('rejection_reason'),
                ]);

                // Update provider rejection metrics
                $this->updateProviderRejectionMetrics($spId);

                // Check if we need to trigger next round or auto-assignment
                $this->checkAndTriggerNextAssignmentRound($task);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task rejected successfully',
                    'data' => [
                        'task_id' => $task->id,
                        'rejection_reason' => $request->input('rejection_reason'),
                        'response_time_seconds' => $responseTime,
                        'next_round_triggered' => true,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle task timeout and trigger auto-assignment
     * 
     * @param int $taskId
     * @return JsonResponse
     */
    public function handleTaskTimeout(int $taskId): JsonResponse
    {
        try {
            $task = Task::find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            if (!in_array($task->status, [Task::STATUS_REQUESTED, Task::STATUS_SEARCHING])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task is not in a state that can timeout',
                    'current_status' => $task->status
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Mark all pending broadcasts as timeout
                $pendingBroadcasts = TaskBroadcast::where('task_id', $taskId)
                    ->where('response', 'pending')
                    ->where('expires_at', '<', now())
                    ->get();

                foreach ($pendingBroadcasts as $broadcast) {
                    $broadcast->update([
                        'response' => 'timeout',
                        'responded_at' => now(),
                    ]);

                    // Log timeout
                    TaskAssignmentLog::create([
                        'task_id' => $taskId,
                        'service_provider_id' => $broadcast->service_provider_id,
                        'action_type' => TaskAssignmentLog::ACTION_TIMEOUT,
                        'action_timestamp' => now(),
                        'assignment_round' => $broadcast->broadcast_round,
                        'broadcast_id' => $broadcast->id,
                    ]);
                }

                // Trigger auto-assignment
                $autoAssignmentResult = $this->triggerAutoAssignment($task);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task timeout handled and auto-assignment triggered',
                    'data' => [
                        'task_id' => $taskId,
                        'timeout_broadcasts' => $pendingBroadcasts->count(),
                        'auto_assignment_result' => $autoAssignmentResult,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to handle task timeout',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get filtered tasks for a specific provider based on capabilities
     */
    private function getFilteredTasksForProvider(ServiceProvider $serviceProvider, array $filters = []): array
    {
        $query = Task::with(['customer', 'customerAddress', 'category', 'subcategory', 'service'])
            ->whereIn('status', [Task::STATUS_REQUESTED, Task::STATUS_SEARCHING])
            ->where('scheduled_at', '>', now());

        // Filter by provider capabilities
        $providerCapabilities = $serviceProvider->capabilities()
            ->where('is_active', true)
            ->get();

        if ($providerCapabilities->isNotEmpty()) {
            $categoryIds = $providerCapabilities->pluck('category_id')->unique();
            $subcategoryIds = $providerCapabilities->pluck('subcategory_id')->unique();

            $query->where(function ($q) use ($categoryIds, $subcategoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->orWhereIn('subcategory_id', $subcategoryIds);
            });
        }

        // Apply distance filter if location provided
        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $maxDistance = $filters['max_distance_km'] ?? 25;
            $lat = $filters['latitude'];
            $lng = $filters['longitude'];

            $query->whereHas('customerAddress', function ($q) use ($lat, $lng, $maxDistance) {
                $q->whereRaw("
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                    sin(radians(latitude)))) <= ?
                ", [$lat, $lng, $lat, $maxDistance]);
            });
        }

        $limit = $filters['limit'] ?? 20;
        $tasks = $query->limit($limit)->get();

        return $tasks->map(function ($task) use ($serviceProvider, $filters) {
            $distance = null;
            if (!empty($filters['latitude']) && !empty($filters['longitude']) && $task->customerAddress) {
                $distance = $this->calculateDistance(
                    $filters['latitude'],
                    $filters['longitude'],
                    $task->customerAddress->latitude,
                    $task->customerAddress->longitude
                );
            }

            return [
                'id' => $task->id,
                'task_number' => $task->task_number,
                'status' => $task->status,
                'customer' => [
                    'name' => $task->customer->name,
                    'phone' => $task->customer->phone,
                ],
                'service' => [
                    'category' => $task->category->name,
                    'subcategory' => $task->subcategory->name,
                    'service' => $task->service->name,
                ],
                'details' => [
                    'pax_count' => $task->pax_count,
                    'requested_hours' => $task->requested_hours,
                    'scheduled_at' => $task->scheduled_at->toISOString(),
                    'special_instructions' => $task->special_instructions,
                ],
                'location' => [
                    'address' => $task->customerAddress->address_line_1 . ', ' . $task->customerAddress->city,
                    'latitude' => $task->customerAddress->latitude,
                    'longitude' => $task->customerAddress->longitude,
                    'distance_km' => $distance,
                ],
                'pricing' => [
                    'total_amount' => $task->total_amount,
                    'final_amount' => $task->final_amount,
                ],
                'broadcast_info' => $this->getTaskBroadcastInfo($task->id, $serviceProvider->id),
            ];
        })->toArray();
    }

    /**
     * Get broadcast information for a task and provider
     */
    private function getTaskBroadcastInfo(int $taskId, int $spId): ?array
    {
        $broadcast = TaskBroadcast::where('task_id', $taskId)
            ->where('service_provider_id', $spId)
            ->where('response', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$broadcast) {
            return null;
        }

        return [
            'broadcast_id' => $broadcast->id,
            'sent_at' => $broadcast->sent_at->toISOString(),
            'expires_at' => $broadcast->expires_at->toISOString(),
            'timeout_seconds' => $broadcast->timeout_seconds,
            'time_remaining' => max(0, $broadcast->expires_at->diffInSeconds(now())),
            'broadcast_round' => $broadcast->broadcast_round,
        ];
    }

    /**
     * Resolve task assignment conflict
     */
    private function resolveTaskAssignmentConflict(Task $task, $conflictingAcceptances): array
    {
        Log::info("Resolving task assignment conflict for task {$task->id} with {$conflictingAcceptances->count()} acceptances");

        // Sort by priority score (highest first)
        $sortedAcceptances = $conflictingAcceptances->sortByDesc('priority_score');
        $winner = $sortedAcceptances->first();

        // Log conflict resolution for all participants
        foreach ($conflictingAcceptances as $acceptance) {
            $acceptance->update([
                'conflict_resolution_applied' => true,
                'metadata' => array_merge($acceptance->metadata ?? [], [
                    'conflict_resolution' => [
                        'total_participants' => $conflictingAcceptances->count(),
                        'winner_sp_id' => $winner->service_provider_id,
                        'winner_score' => $winner->priority_score,
                        'this_score' => $acceptance->priority_score,
                        'resolved_at' => now()->toISOString(),
                    ]
                ])
            ]);

            if ($acceptance->id !== $winner->id) {
                TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $acceptance->service_provider_id,
                    'action_type' => TaskAssignmentLog::ACTION_CONFLICT_RESOLVED,
                    'action_timestamp' => now(),
                    'assignment_round' => $acceptance->assignment_round,
                    'broadcast_id' => $acceptance->broadcast_id,
                    'conflict_resolution_applied' => true,
                    'metadata' => [
                        'conflict_result' => 'lost',
                        'winner_sp_id' => $winner->service_provider_id,
                        'priority_score' => $acceptance->priority_score,
                        'winner_score' => $winner->priority_score,
                    ],
                ]);
            }
        }

        return [
            'winner_sp_id' => $winner->service_provider_id,
            'winner_score' => $winner->priority_score,
            'total_participants' => $conflictingAcceptances->count(),
        ];
    }

    /**
     * Assign task to provider
     */
    private function assignTaskToProvider(Task $task, TaskBroadcast $broadcast, int $spId): void
    {
        // Update task
        $task->update([
            'service_provider_id' => $spId,
            'status' => Task::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        // Update broadcast
        $broadcast->update([
            'response' => 'accepted',
            'responded_at' => now(),
        ]);

        // Cancel all other pending broadcasts for this task
        TaskBroadcast::where('task_id', $task->id)
            ->where('id', '!=', $broadcast->id)
            ->where('response', 'pending')
            ->update([
                'response' => 'cancelled',
                'responded_at' => now(),
            ]);

        // Send notifications
        $this->notificationService->sendTaskAssignmentNotification($task);

        Log::info("Task {$task->id} assigned to provider {$spId}");
    }

    /**
     * Check and trigger next assignment round
     */
    private function checkAndTriggerNextAssignmentRound(Task $task): void
    {
        // Check if all current round broadcasts are responded to
        $pendingBroadcasts = TaskBroadcast::where('task_id', $task->id)
            ->where('response', 'pending')
            ->where('expires_at', '>', now())
            ->count();

        if ($pendingBroadcasts === 0) {
            // All current broadcasts are done, check if we need next round
            $totalRejections = TaskBroadcast::where('task_id', $task->id)
                ->where('response', 'rejected')
                ->count();

            if ($totalRejections >= 3) {
                // Too many rejections, trigger auto-assignment
                $this->triggerAutoAssignment($task);
            } else {
                // Try next round with different providers
                $this->taskAllocationService->initiateNextRound($task);
            }
        }
    }

    /**
     * Trigger auto-assignment
     */
    private function triggerAutoAssignment(Task $task): array
    {
        Log::info("Triggering auto-assignment for task {$task->id}");

        // Find the best available provider using existing allocation service
        $result = $this->taskAllocationService->autoAssignBestProvider($task);

        if ($result['success']) {
            TaskAssignmentLog::create([
                'task_id' => $task->id,
                'service_provider_id' => $result['provider_id'],
                'action_type' => TaskAssignmentLog::ACTION_AUTO_ASSIGNED,
                'action_timestamp' => now(),
                'auto_assigned' => true,
                'metadata' => [
                    'auto_assignment_reason' => 'timeout_fallback',
                    'assignment_score' => $result['score'] ?? null,
                ],
            ]);
        }

        return $result;
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}