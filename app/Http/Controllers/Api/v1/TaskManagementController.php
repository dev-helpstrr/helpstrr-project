<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\Customer;
use App\Models\SpLocationTracking;
use App\Services\TaskAllocationService;
use App\Services\NotificationService;
use App\Services\PriceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskManagementController extends Controller
{
    protected $taskAllocationService;
    protected $notificationService;
    protected $priceCalculationService;

    public function __construct(
        TaskAllocationService $taskAllocationService,
        NotificationService $notificationService,
        PriceCalculationService $priceCalculationService
    ) {
        $this->taskAllocationService = $taskAllocationService;
        $this->notificationService = $notificationService;
        $this->priceCalculationService = $priceCalculationService;
    }

    /**
     * Update task status
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function updateTaskStatus(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:assigned,on_the_way,arrived,otp_start_verified,started,paused,resumed,completed,cancelled',
                'sp_id' => 'nullable|exists:service_providers,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'notes' => 'nullable|string|max:500',
                'otp' => 'nullable|string|size:6',
                'cancellation_reason' => 'nullable|string|max:500',
                'cancelled_by' => 'nullable|string|in:customer,service_provider,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $task = Task::find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Validate status transition
            if (!$task->canTransitionTo($data['status'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'current_status' => $task->status,
                    'requested_status' => $data['status']
                ], 400);
            }

            DB::beginTransaction();

            try {
                $updateData = ['status' => $data['status']];

                // Handle specific status updates
                switch ($data['status']) {
                    case Task::STATUS_ASSIGNED:
                        if (empty($data['sp_id'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Service provider ID is required for assignment'
                            ], 400);
                        }
                        
                        $serviceProvider = ServiceProvider::find($data['sp_id']);
                        if (!$serviceProvider || !$serviceProvider->isAvailable()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Service provider is not available'
                            ], 400);
                        }

                        $updateData['service_provider_id'] = $data['sp_id'];
                        $updateData['assigned_at'] = now();
                        break;

                    case Task::STATUS_ON_THE_WAY:
                        // Update SP location if provided
                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $this->updateServiceProviderLocation($task->service_provider_id, $data['latitude'], $data['longitude']);
                        }
                        break;

                    case Task::STATUS_ARRIVED:
                        // Verify SP is at customer location (optional validation)
                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $distance = $this->calculateDistance(
                                $data['latitude'], 
                                $data['longitude'],
                                $task->customerAddress->latitude,
                                $task->customerAddress->longitude
                            );
                            
                            if ($distance > 0.5) { // 500 meters tolerance
                                return response()->json([
                                    'success' => false,
                                    'message' => 'You must be at the customer location to mark as arrived',
                                    'distance_km' => round($distance, 2)
                                ], 400);
                            }
                        }
                        break;

                    case Task::STATUS_OTP_START_VERIFIED:
                        if (empty($data['otp'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'OTP is required to start the task'
                            ], 400);
                        }

                        if (!$task->verifyStartOTP($data['otp'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Invalid OTP'
                            ], 400);
                        }
                        break;

                    case Task::STATUS_STARTED:
                        $updateData['started_at'] = now();
                        break;

                    case Task::STATUS_PAUSED:
                        if (empty($data['notes'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Reason is required to pause the task'
                            ], 400);
                        }
                        break;

                    case Task::STATUS_COMPLETED:
                        if (!empty($data['otp'])) {
                            if (!$task->verifyEndOTP($data['otp'])) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Invalid completion OTP'
                                ], 400);
                            }
                        }

                        $updateData['completed_at'] = now();
                        
                        // Calculate actual billable hours
                        if ($task->started_at) {
                            $actualHours = $task->started_at->diffInHours(now(), true);
                            $updateData['billable_hours'] = max(1, ceil($actualHours)); // Minimum 1 hour
                        }
                        break;

                    case 'cancelled':
                        if (empty($data['cancellation_reason']) || empty($data['cancelled_by'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Cancellation reason and cancelled_by are required'
                            ], 400);
                        }

                        $updateData['cancelled_at'] = now();
                        $updateData['cancellation_reason'] = $data['cancellation_reason'];
                        $updateData['cancelled_by'] = $data['cancelled_by'];
                        break;
                }

                // Update task
                $task->update($updateData);

                // Send notifications
                $this->notificationService->sendTaskStatusNotification($task, $data['status']);

                // Handle post-status update actions
                if ($data['status'] === Task::STATUS_COMPLETED) {
                    // Update service provider metrics
                    $this->updateServiceProviderMetrics($task);
                    
                    // Generate end OTP for customer rating
                    $task->generateEndOTP();
                }

                DB::commit();

                // Load fresh task data
                $task->load([
                    'customer',
                    'customerAddress',
                    'category',
                    'subcategory',
                    'service',
                    'serviceProvider.spUser',
                    'priceComponents'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task status updated successfully',
                    'data' => [
                        'task' => $this->formatTaskResponse($task),
                        'next_actions' => $this->getNextActions($task),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cancel a task
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function cancelTask(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
                'cancelled_by' => 'required|string|in:customer,service_provider,admin',
                'user_id' => 'required|integer',
                'user_type' => 'required|string|in:customer,service_provider,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $task = Task::find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Validate cancellation permissions
            if (!$this->canCancelTask($task, $data['user_type'], $data['user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to cancel this task'
                ], 403);
            }

            // Check if task can be cancelled
            if (in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED, 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task cannot be cancelled in current status',
                    'current_status' => $task->status
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Calculate cancellation charges
                $cancellationCharges = $this->calculateCancellationCharges($task, $data['cancelled_by']);

                // Cancel the task
                $task->cancel($data['reason'], $data['cancelled_by']);

                // Update service provider metrics if assigned
                if ($task->service_provider_id && $data['cancelled_by'] === 'service_provider') {
                    $this->updateServiceProviderCancellationMetrics($task->serviceProvider);
                }

                // Send notifications
                $this->notificationService->sendTaskCancellationNotification($task, $cancellationCharges);

                // If task was assigned, trigger reallocation
                if ($task->service_provider_id && $data['cancelled_by'] === 'service_provider') {
                    $this->taskAllocationService->initiateReallocation($task);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task cancelled successfully',
                    'data' => [
                        'task_id' => $task->id,
                        'task_number' => $task->task_number,
                        'cancellation_charges' => $cancellationCharges,
                        'refund_amount' => max(0, $task->final_amount - $cancellationCharges),
                        'cancelled_at' => $task->cancelled_at->toISOString(),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Rate a task
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function rateTask(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string|max:1000',
                'rated_by' => 'required|string|in:customer,service_provider',
                'user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $task = Task::find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Validate task status
            if (!in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task must be completed before rating',
                    'current_status' => $task->status
                ], 400);
            }

            // Validate rating permissions
            if (!$this->canRateTask($task, $data['rated_by'], $data['user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to rate this task'
                ], 403);
            }

            DB::beginTransaction();

            try {
                if ($data['rated_by'] === 'customer') {
                    // Check if already rated by customer
                    if ($task->customer_rating) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Task already rated by customer'
                        ], 400);
                    }

                    $task->rateByCustomer($data['rating'], $data['feedback']);
                } else {
                    // Check if already rated by service provider
                    if ($task->sp_rating) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Task already rated by service provider'
                        ], 400);
                    }

                    $task->rateBySP($data['rating'], $data['feedback']);
                }

                // Send notification
                $this->notificationService->sendTaskRatingNotification($task, $data['rated_by']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task rated successfully',
                    'data' => [
                        'task_id' => $task->id,
                        'task_number' => $task->task_number,
                        'customer_rating' => $task->customer_rating,
                        'customer_feedback' => $task->customer_feedback,
                        'sp_rating' => $task->sp_rating,
                        'sp_feedback' => $task->sp_feedback,
                        'status' => $task->status,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rate task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get task details
     * 
     * @param int $taskId
     * @return JsonResponse
     */
    public function getTaskDetails(int $taskId): JsonResponse
    {
        try {
            $task = Task::with([
                'customer',
                'customerAddress',
                'category',
                'subcategory',
                'service',
                'serviceProvider.spUser',
                'dietaryPreference',
                'priceComponents',
                'selectedCuisines',
                'addonFlags',
                'optionalFlags',
                'broadcasts',
                'issues'
            ])->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task details retrieved successfully',
                'data' => [
                    'task' => $this->formatDetailedTaskResponse($task),
                    'timeline' => $this->getTaskTimeline($task),
                    'next_actions' => $this->getNextActions($task),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to format task response
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
            'service' => [
                'category' => $task->category->name,
                'subcategory' => $task->subcategory->name,
                'service' => $task->service->name,
            ],
            'details' => [
                'pax_count' => $task->pax_count,
                'requested_hours' => $task->requested_hours,
                'billable_hours' => $task->billable_hours,
                'scheduled_at' => $task->scheduled_at->toISOString(),
            ],
            'pricing' => [
                'total_amount' => $task->total_amount,
                'gst_amount' => $task->gst_amount,
                'final_amount' => $task->final_amount,
            ],
            'service_provider' => $task->serviceProvider ? [
                'id' => $task->serviceProvider->id,
                'name' => $task->serviceProvider->spUser->name,
                'phone' => $task->serviceProvider->spUser->phone,
                'rating' => $task->serviceProvider->rating,
            ] : null,
        ];
    }

    /**
     * Helper method to format detailed task response
     */
    private function formatDetailedTaskResponse(Task $task): array
    {
        $response = $this->formatTaskResponse($task);
        
        // Add detailed information
        $response['address'] = [
            'id' => $task->customerAddress->id,
            'address_line_1' => $task->customerAddress->address_line_1,
            'address_line_2' => $task->customerAddress->address_line_2,
            'city' => $task->customerAddress->city,
            'state' => $task->customerAddress->state,
            'pincode' => $task->customerAddress->pincode,
            'latitude' => $task->customerAddress->latitude,
            'longitude' => $task->customerAddress->longitude,
        ];

        $response['schedule'] = [
            'dates' => $task->dates,
            'start_time' => $task->start_time->format('H:i'),
            'end_time' => $task->end_time->format('H:i'),
            'recurrence_type' => $task->recurrence_type,
            'recurrence_display' => $task->recurrence_display,
            'scheduled_at' => $task->scheduled_at->toISOString(),
            'assigned_at' => $task->assigned_at?->toISOString(),
            'started_at' => $task->started_at?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'cancelled_at' => $task->cancelled_at?->toISOString(),
        ];

        $response['special_instructions'] = $task->special_instructions;
        $response['cancellation_reason'] = $task->cancellation_reason;
        $response['cancelled_by'] = $task->cancelled_by;

        $response['ratings'] = [
            'customer_rating' => $task->customer_rating,
            'customer_feedback' => $task->customer_feedback,
            'sp_rating' => $task->sp_rating,
            'sp_feedback' => $task->sp_feedback,
        ];

        if ($task->priceComponents) {
            $response['price_breakdown'] = $task->priceComponents->getPriceBreakdown();
        }

        return $response;
    }

    /**
     * Get next possible actions for a task
     */
    private function getNextActions(Task $task): array
    {
        $actions = [];

        switch ($task->status) {
            case Task::STATUS_REQUESTED:
            case Task::STATUS_SEARCHING:
                $actions[] = ['action' => 'cancel', 'label' => 'Cancel Booking'];
                break;

            case Task::STATUS_ASSIGNED:
                $actions[] = ['action' => 'update_status', 'status' => 'on_the_way', 'label' => 'Mark On The Way'];
                $actions[] = ['action' => 'cancel', 'label' => 'Cancel Task'];
                break;

            case Task::STATUS_ON_THE_WAY:
                $actions[] = ['action' => 'update_status', 'status' => 'arrived', 'label' => 'Mark Arrived'];
                $actions[] = ['action' => 'cancel', 'label' => 'Cancel Task'];
                break;

            case Task::STATUS_ARRIVED:
                $actions[] = ['action' => 'verify_start_otp', 'label' => 'Verify Start OTP'];
                break;

            case Task::STATUS_OTP_START_VERIFIED:
                $actions[] = ['action' => 'update_status', 'status' => 'started', 'label' => 'Start Task'];
                break;

            case Task::STATUS_STARTED:
                $actions[] = ['action' => 'update_status', 'status' => 'paused', 'label' => 'Pause Task'];
                $actions[] = ['action' => 'update_status', 'status' => 'completed', 'label' => 'Complete Task'];
                break;

            case Task::STATUS_PAUSED:
                $actions[] = ['action' => 'update_status', 'status' => 'resumed', 'label' => 'Resume Task'];
                break;

            case Task::STATUS_RESUMED:
                $actions[] = ['action' => 'update_status', 'status' => 'paused', 'label' => 'Pause Task'];
                $actions[] = ['action' => 'update_status', 'status' => 'completed', 'label' => 'Complete Task'];
                break;

            case Task::STATUS_COMPLETED:
                if (!$task->customer_rating) {
                    $actions[] = ['action' => 'rate_task', 'rated_by' => 'customer', 'label' => 'Rate Service Provider'];
                }
                if (!$task->sp_rating) {
                    $actions[] = ['action' => 'rate_task', 'rated_by' => 'service_provider', 'label' => 'Rate Customer'];
                }
                break;
        }

        return $actions;
    }

    /**
     * Get task timeline
     */
    private function getTaskTimeline(Task $task): array
    {
        $timeline = [];

        $timeline[] = [
            'status' => 'requested',
            'label' => 'Booking Requested',
            'timestamp' => $task->created_at->toISOString(),
            'completed' => true,
        ];

        if ($task->assigned_at) {
            $timeline[] = [
                'status' => 'assigned',
                'label' => 'Service Provider Assigned',
                'timestamp' => $task->assigned_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->started_at) {
            $timeline[] = [
                'status' => 'started',
                'label' => 'Task Started',
                'timestamp' => $task->started_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->completed_at) {
            $timeline[] = [
                'status' => 'completed',
                'label' => 'Task Completed',
                'timestamp' => $task->completed_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->cancelled_at) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Task Cancelled',
                'timestamp' => $task->cancelled_at->toISOString(),
                'completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Helper methods for validation and calculations
     */
    private function canCancelTask(Task $task, string $userType, int $userId): bool
    {
        switch ($userType) {
            case 'customer':
                return $task->customer_id === $userId;
            case 'service_provider':
                return $task->service_provider_id === $userId;
            case 'admin':
                return true;
            default:
                return false;
        }
    }

    private function canRateTask(Task $task, string $ratedBy, int $userId): bool
    {
        switch ($ratedBy) {
            case 'customer':
                return $task->customer_id === $userId;
            case 'service_provider':
                return $task->serviceProvider && $task->serviceProvider->spUser->id === $userId;
            default:
                return false;
        }
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    private function calculateCancellationCharges(Task $task, string $cancelledBy): float
    {
        // Implement cancellation charge logic based on your business rules
        if ($cancelledBy === 'customer') {
            $hoursUntilScheduled = now()->diffInHours($task->scheduled_at, false);
            
            if ($hoursUntilScheduled < 2) {
                return $task->final_amount * 0.5; // 50% charge if cancelled within 2 hours
            } elseif ($hoursUntilScheduled < 24) {
                return $task->final_amount * 0.25; // 25% charge if cancelled within 24 hours
            }
        }

        return 0; // No charge for other cases
    }

    private function updateServiceProviderLocation(int $spId, float $latitude, float $longitude): void
    {
        // Create location tracking entry
        SpLocationTracking::create([
            'service_provider_id' => $spId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => 10, // Default accuracy
            'is_active' => true,
            'recorded_at' => now(),
        ]);

        // Update SP user location
        $sp = ServiceProvider::find($spId);
        if ($sp && $sp->spUser) {
            $sp->spUser->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'last_seen_at' => now(),
            ]);
        }
    }

    private function updateServiceProviderMetrics(Task $task): void
    {
        if ($task->serviceProvider) {
            $sp = $task->serviceProvider;
            $sp->increment('tasks_completed');
            
            // Update punctuality score based on scheduled vs actual completion time
            // Implementation depends on your scoring algorithm
        }
    }

    private function updateServiceProviderCancellationMetrics(ServiceProvider $sp): void
    {
        $sp->increment('tasks_cancelled');
        
        // Update cancellation score
        $totalTasks = $sp->tasks_completed + $sp->tasks_cancelled;
        if ($totalTasks > 0) {
            $cancellationRate = ($sp->tasks_cancelled / $totalTasks) * 100;
            $sp->update(['cancellation_score' => $cancellationRate]);
        }
    }

    /**
     * Get cancellation preview with charges
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function getCancellationPreview(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cancelled_by' => 'required|string|in:customer,service_provider,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $cancelledBy = $request->input('cancelled_by');
            $cancellationCharges = $this->calculateAdvancedCancellationCharges($task, $cancelledBy);
            $refundAmount = max(0, $task->final_amount - $cancellationCharges);

            return response()->json([
                'success' => true,
                'data' => [
                    'task_id' => $task->id,
                    'task_number' => $task->task_number,
                    'current_status' => $task->status,
                    'can_cancel' => !in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED, 'cancelled']),
                    'cancellation_policy' => $this->getCancellationPolicy($task, $cancelledBy),
                    'charges' => [
                        'original_amount' => $task->final_amount,
                        'cancellation_charges' => $cancellationCharges,
                        'refund_amount' => $refundAmount,
                        'charge_percentage' => $task->final_amount > 0 ? round(($cancellationCharges / $task->final_amount) * 100, 1) : 0,
                    ],
                    'timeline' => [
                        'scheduled_at' => $task->scheduled_at->toISOString(),
                        'hours_until_scheduled' => now()->diffInHours($task->scheduled_at, false),
                        'can_cancel_free' => now()->diffInHours($task->scheduled_at, false) > 24,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cancellation preview',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available cancellation reasons
     * 
     * @return JsonResponse
     */
    public function getCancellationReasons(): JsonResponse
    {
        $reasons = [
            'customer' => [
                'change_of_plans' => 'Change of plans',
                'emergency' => 'Emergency situation',
                'provider_delay' => 'Service provider is running late',
                'provider_no_show' => 'Service provider did not show up',
                'quality_concerns' => 'Concerns about service quality',
                'pricing_issue' => 'Pricing discrepancy',
                'other' => 'Other reason',
            ],
            'service_provider' => [
                'emergency' => 'Personal emergency',
                'vehicle_breakdown' => 'Vehicle breakdown',
                'health_issue' => 'Health issue',
                'customer_unreachable' => 'Customer unreachable',
                'unsafe_location' => 'Unsafe location/environment',
                'customer_behavior' => 'Inappropriate customer behavior',
                'other' => 'Other reason',
            ],
            'admin' => [
                'system_error' => 'System error',
                'policy_violation' => 'Policy violation',
                'safety_concern' => 'Safety concern',
                'fraud_detection' => 'Fraud detection',
                'other' => 'Other reason',
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reasons
        ]);
    }

    /**
     * Submit comprehensive rating for a task
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function submitComprehensiveRating(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rated_by' => 'required|string|in:customer,service_provider',
                'user_id' => 'required|integer',
                'overall_rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string|max:1000',
                'category_ratings' => 'nullable|array',
                'category_ratings.*.category' => 'required|string',
                'category_ratings.*.rating' => 'required|integer|min:1|max:5',
                'tip_amount' => 'nullable|numeric|min:0|max:1000',
                'would_recommend' => 'nullable|boolean',
                'service_issues' => 'nullable|array',
                'service_issues.*' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $task = Task::find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Validate rating permissions
            if (!$this->canRateTask($task, $data['rated_by'], $data['user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to rate this task'
                ], 403);
            }

            // Check if task is completed
            if ($task->status !== Task::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task must be completed before rating',
                    'current_status' => $task->status
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Update task with rating
                $updateData = [];
                if ($data['rated_by'] === 'customer') {
                    if ($task->customer_rating) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Customer rating already submitted'
                        ], 400);
                    }
                    
                    $updateData['customer_rating'] = $data['overall_rating'];
                    $updateData['customer_feedback'] = $data['feedback'] ?? '';
                    $updateData['customer_category_ratings'] = json_encode($data['category_ratings'] ?? []);
                    $updateData['customer_tip_amount'] = $data['tip_amount'] ?? 0;
                    $updateData['customer_would_recommend'] = $data['would_recommend'] ?? null;
                    $updateData['customer_service_issues'] = json_encode($data['service_issues'] ?? []);
                    $updateData['customer_rated_at'] = now();
                    
                } else {
                    if ($task->sp_rating) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Service provider rating already submitted'
                        ], 400);
                    }
                    
                    $updateData['sp_rating'] = $data['overall_rating'];
                    $updateData['sp_feedback'] = $data['feedback'] ?? '';
                    $updateData['sp_category_ratings'] = json_encode($data['category_ratings'] ?? []);
                    $updateData['sp_rated_at'] = now();
                }

                $task->update($updateData);

                // Update service provider rating if customer rated
                if ($data['rated_by'] === 'customer' && $task->serviceProvider) {
                    $task->serviceProvider->updateRating($data['overall_rating']);
                }

                // Update task status to rated if both parties have rated
                if ($task->customer_rating && $task->sp_rating) {
                    $task->update(['status' => Task::STATUS_RATED]);
                }

                // Send notifications
                $this->notificationService->sendRatingNotification($task, $data['rated_by']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Rating submitted successfully',
                    'data' => [
                        'task_id' => $task->id,
                        'task_number' => $task->task_number,
                        'rating' => $data['overall_rating'],
                        'rated_by' => $data['rated_by'],
                        'rated_at' => now()->toISOString(),
                        'both_rated' => $task->customer_rating && $task->sp_rating,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get rating form for a task
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function getRatingForm(Request $request, int $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rated_by' => 'required|string|in:customer,service_provider',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $ratedBy = $request->input('rated_by');
            
            // Check if already rated
            $alreadyRated = ($ratedBy === 'customer' && $task->customer_rating) || 
                          ($ratedBy === 'service_provider' && $task->sp_rating);

            if ($alreadyRated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating already submitted for this task'
                ], 400);
            }

            $form = $this->generateRatingForm($task, $ratedBy);

            return response()->json([
                'success' => true,
                'data' => $form
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get rating form',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Advanced cancellation charges calculation
     */
    private function calculateAdvancedCancellationCharges(Task $task, string $cancelledBy): float
    {
        if ($cancelledBy !== 'customer') {
            return 0; // No charges for SP or admin cancellations
        }

        $hoursUntilScheduled = now()->diffInHours($task->scheduled_at, false);
        $baseAmount = $task->final_amount;

        // Time-based cancellation policy
        if ($hoursUntilScheduled < 1) {
            return $baseAmount * 0.75; // 75% charge if cancelled within 1 hour
        } elseif ($hoursUntilScheduled < 2) {
            return $baseAmount * 0.50; // 50% charge if cancelled within 2 hours
        } elseif ($hoursUntilScheduled < 6) {
            return $baseAmount * 0.25; // 25% charge if cancelled within 6 hours
        } elseif ($hoursUntilScheduled < 24) {
            return $baseAmount * 0.10; // 10% charge if cancelled within 24 hours
        }

        return 0; // No charge if cancelled more than 24 hours in advance
    }

    /**
     * Get cancellation policy details
     */
    private function getCancellationPolicy(Task $task, string $cancelledBy): array
    {
        return [
            'free_cancellation_hours' => 24,
            'charge_tiers' => [
                ['hours' => 24, 'charge_percentage' => 0, 'description' => 'Free cancellation'],
                ['hours' => 6, 'charge_percentage' => 10, 'description' => '10% cancellation fee'],
                ['hours' => 2, 'charge_percentage' => 25, 'description' => '25% cancellation fee'],
                ['hours' => 1, 'charge_percentage' => 50, 'description' => '50% cancellation fee'],
                ['hours' => 0, 'charge_percentage' => 75, 'description' => '75% cancellation fee'],
            ],
            'notes' => [
                'Cancellation charges apply only to customer cancellations',
                'Service provider cancellations do not incur charges',
                'Emergency cancellations may be reviewed case by case',
            ]
        ];
    }

    /**
     * Generate rating form based on task type and rater
     */
    private function generateRatingForm(Task $task, string $ratedBy): array
    {
        $form = [
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'rated_by' => $ratedBy,
            'overall_rating' => [
                'label' => 'Overall Rating',
                'type' => 'rating',
                'min' => 1,
                'max' => 5,
                'required' => true,
            ],
            'feedback' => [
                'label' => 'Feedback',
                'type' => 'textarea',
                'placeholder' => 'Share your experience...',
                'max_length' => 1000,
                'required' => false,
            ],
        ];

        if ($ratedBy === 'customer') {
            $form['category_ratings'] = [
                [
                    'category' => 'punctuality',
                    'label' => 'Punctuality',
                    'description' => 'Was the service provider on time?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
                [
                    'category' => 'quality',
                    'label' => 'Service Quality',
                    'description' => 'How was the quality of service?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
                [
                    'category' => 'behavior',
                    'label' => 'Behavior',
                    'description' => 'How was the service provider\'s behavior?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
                [
                    'category' => 'cleanliness',
                    'label' => 'Cleanliness',
                    'description' => 'How clean was the service provider?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
            ];

            $form['tip_amount'] = [
                'label' => 'Tip Amount (Optional)',
                'type' => 'number',
                'min' => 0,
                'max' => 1000,
                'step' => 10,
                'currency' => 'INR',
                'required' => false,
            ];

            $form['would_recommend'] = [
                'label' => 'Would you recommend this service provider?',
                'type' => 'boolean',
                'required' => false,
            ];

            $form['service_issues'] = [
                'label' => 'Any issues with the service? (Select all that apply)',
                'type' => 'multi_select',
                'options' => [
                    'late_arrival' => 'Late arrival',
                    'poor_quality' => 'Poor service quality',
                    'unprofessional' => 'Unprofessional behavior',
                    'incomplete_work' => 'Incomplete work',
                    'cleanliness' => 'Cleanliness issues',
                    'communication' => 'Communication problems',
                    'other' => 'Other',
                ],
                'required' => false,
            ];
        } else {
            $form['category_ratings'] = [
                [
                    'category' => 'cooperation',
                    'label' => 'Customer Cooperation',
                    'description' => 'How cooperative was the customer?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
                [
                    'category' => 'communication',
                    'label' => 'Communication',
                    'description' => 'How clear was the customer\'s communication?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
                [
                    'category' => 'environment',
                    'label' => 'Work Environment',
                    'description' => 'How was the work environment?',
                    'type' => 'rating',
                    'min' => 1,
                    'max' => 5,
                ],
            ];
        }

        return $form;
    }
}