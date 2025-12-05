<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\SPUser;
use App\Models\TaskBroadcast;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceProviderTaskController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
}