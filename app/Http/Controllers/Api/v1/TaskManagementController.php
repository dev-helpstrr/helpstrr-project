<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\Customer;
use App\Services\TaskAllocationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskManagementController extends Controller
{
    protected $taskAllocationService;
    protected $notificationService;

    public function __construct(
        TaskAllocationService $taskAllocationService,
        NotificationService $notificationService
    ) {
        $this->taskAllocationService = $taskAllocationService;
        $this->notificationService = $notificationService;
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
        // Update service provider location in location tracking table
        // Implementation depends on your location tracking model
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
}