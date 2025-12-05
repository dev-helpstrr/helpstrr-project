<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\Customer;
use App\Services\PricingEngine;
use App\Services\AllocationEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskLifecycleService
{
    protected PricingEngine $pricingEngine;
    protected AllocationEngine $allocationEngine;

    public function __construct(PricingEngine $pricingEngine, AllocationEngine $allocationEngine)
    {
        $this->pricingEngine = $pricingEngine;
        $this->allocationEngine = $allocationEngine;
    }

    /**
     * Create a new task and initiate the lifecycle
     * This is the OFFICIAL TASK CREATION FLOW
     */
    public function createTask(array $taskData): array
    {
        try {
            DB::beginTransaction();

            // Step 1: Validate lead time (HARD BLOCK)
            $scheduledAt = Carbon::parse($taskData['scheduled_at']);
            if (!$this->pricingEngine->validateLeadTime($scheduledAt)) {
                return [
                    'success' => false,
                    'message' => 'Booking must be scheduled at least 2 hours in advance',
                    'error_code' => 'LEAD_TIME_VIOLATION'
                ];
            }

            // Step 2: Calculate pricing
            $pricingData = $this->pricingEngine->calculateTaskPricing($taskData);

            // Step 3: Create task
            $task = Task::create([
                'customer_id' => $taskData['customer_id'],
                'customer_address_id' => $taskData['customer_address_id'],
                'category_id' => $taskData['category_id'],
                'subcategory_id' => $taskData['subcategory_id'],
                'pax_count' => $taskData['pax_count'] ?? null,
                'requested_hours' => $taskData['requested_hours'],
                'billable_hours' => $pricingData['billable_hours'],
                'dates' => $taskData['dates'] ?? [],
                'start_time' => $taskData['start_time'],
                'end_time' => $taskData['end_time'],
                'recurrence_type' => $taskData['recurrence_type'] ?? Task::RECURRENCE_ONE_TIME,
                'dietary_preference_id' => $taskData['dietary_preference_id'] ?? null,
                'scheduled_at' => $scheduledAt,
                'total_amount' => $pricingData['total_excl_gst'],
                'gst_amount' => $pricingData['gst_amount'],
                'final_amount' => $pricingData['total_incl_gst'],
                'special_instructions' => $taskData['special_instructions'] ?? null,
                'status' => Task::STATUS_REQUESTED,
            ]);

            // Step 4: Store pricing components
            $this->pricingEngine->storePricingComponents($task, $pricingData);

            // Step 5: Attach selected cuisines (Chef only)
            if (isset($taskData['selected_cuisines']) && !empty($taskData['selected_cuisines'])) {
                $task->selectedCuisines()->attach($taskData['selected_cuisines']);
            }

            // Step 6: Attach addon flags (Chef only)
            if (isset($taskData['addon_flags']) && !empty($taskData['addon_flags'])) {
                $task->addonFlags()->attach($taskData['addon_flags']);
            }

            // Step 7: Attach optional flags
            if (isset($taskData['optional_flags']) && !empty($taskData['optional_flags'])) {
                $task->optionalFlags()->attach($taskData['optional_flags']);
            }

            // Step 8: Transition to searching
            $this->transitionToSearching($task);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task' => $task->load(['priceComponents', 'selectedCuisines', 'addonFlags', 'optionalFlags']),
                'pricing' => $this->pricingEngine->getCustomerDisplayPrice($pricingData)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task creation failed', [
                'error' => $e->getMessage(),
                'task_data' => $taskData
            ]);

            return [
                'success' => false,
                'message' => 'Task creation failed: ' . $e->getMessage(),
                'error_code' => 'TASK_CREATION_FAILED'
            ];
        }
    }

    /**
     * Transition task to searching status and initiate SP allocation
     */
    public function transitionToSearching(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_SEARCHING)) {
            return false;
        }

        // Initiate SP allocation
        $allocationResult = $this->allocationEngine->allocateServiceProviders($task);

        if (!$allocationResult['success']) {
            // No SPs found - mark task as failed
            $task->update([
                'status' => 'no_sp_available',
                'cancellation_reason' => $allocationResult['message'],
            ]);
            return false;
        }

        Log::info('Task allocation initiated', [
            'task_id' => $task->id,
            'broadcasts_sent' => count($allocationResult['broadcasts'])
        ]);

        return true;
    }

    /**
     * Handle SP acceptance and transition to assigned
     */
    public function assignToServiceProvider(Task $task, ServiceProvider $serviceProvider): bool
    {
        if (!$task->transitionTo(Task::STATUS_ASSIGNED)) {
            return false;
        }

        $task->update([
            'service_provider_id' => $serviceProvider->id,
            'assigned_at' => now(),
        ]);

        // Generate start OTP
        $task->generateStartOTP();

        // Update SP's last assigned timestamp
        $serviceProvider->update(['last_assigned_at' => now()]);

        Log::info('Task assigned to SP', [
            'task_id' => $task->id,
            'sp_id' => $serviceProvider->id
        ]);

        return true;
    }

    /**
     * SP indicates they are on the way
     */
    public function markOnTheWay(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_ON_THE_WAY)) {
            return false;
        }

        Log::info('SP marked on the way', ['task_id' => $task->id]);
        return true;
    }

    /**
     * SP indicates they have arrived
     */
    public function markArrived(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_ARRIVED)) {
            return false;
        }

        Log::info('SP marked arrived', ['task_id' => $task->id]);
        return true;
    }

    /**
     * Verify start OTP and transition to OTP verified
     */
    public function verifyStartOTP(Task $task, string $otp): array
    {
        if ($task->verifyStartOTP($otp)) {
            Log::info('Start OTP verified', ['task_id' => $task->id]);
            return [
                'success' => true,
                'message' => 'Start OTP verified successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid OTP'
        ];
    }

    /**
     * Start the task
     */
    public function startTask(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_STARTED)) {
            return false;
        }

        // Generate end OTP
        $task->generateEndOTP();

        Log::info('Task started', ['task_id' => $task->id]);
        return true;
    }

    /**
     * Pause the task
     */
    public function pauseTask(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_PAUSED)) {
            return false;
        }

        Log::info('Task paused', ['task_id' => $task->id]);
        return true;
    }

    /**
     * Resume the task
     */
    public function resumeTask(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_RESUMED)) {
            return false;
        }

        Log::info('Task resumed', ['task_id' => $task->id]);
        return true;
    }

    /**
     * Complete the task (can be from started or resumed status)
     */
    public function completeTask(Task $task): bool
    {
        if (!$task->transitionTo(Task::STATUS_COMPLETED)) {
            return false;
        }

        // Update SP statistics
        $this->updateServiceProviderStats($task->serviceProvider, 'completed');

        Log::info('Task completed', ['task_id' => $task->id]);
        return true;
    }

    /**
     * Verify end OTP and complete task
     */
    public function verifyEndOTP(Task $task, string $otp): array
    {
        if ($task->verifyEndOTP($otp)) {
            // Update SP statistics
            $this->updateServiceProviderStats($task->serviceProvider, 'completed');

            Log::info('End OTP verified and task completed', ['task_id' => $task->id]);
            return [
                'success' => true,
                'message' => 'Task completed successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid OTP'
        ];
    }

    /**
     * Rate the task by customer
     */
    public function rateByCustomer(Task $task, int $rating, ?string $feedback = null): bool
    {
        $task->rateByCustomer($rating, $feedback);

        Log::info('Task rated by customer', [
            'task_id' => $task->id,
            'rating' => $rating
        ]);

        return true;
    }

    /**
     * Rate the task by service provider
     */
    public function rateBySP(Task $task, int $rating, ?string $feedback = null): bool
    {
        $task->rateBySP($rating, $feedback);

        Log::info('Task rated by SP', [
            'task_id' => $task->id,
            'rating' => $rating
        ]);

        return true;
    }

    /**
     * Cancel task with reason
     */
    public function cancelTask(Task $task, string $reason, string $cancelledBy): array
    {
        try {
            DB::beginTransaction();

            $task->cancel($reason, $cancelledBy);

            // Update SP statistics if assigned
            if ($task->serviceProvider) {
                $this->updateServiceProviderStats($task->serviceProvider, 'cancelled');
            }

            // Cancel pending broadcasts
            $task->broadcasts()->where('response', 'pending')->update(['response' => 'cancelled']);

            DB::commit();

            Log::info('Task cancelled', [
                'task_id' => $task->id,
                'reason' => $reason,
                'cancelled_by' => $cancelledBy
            ]);

            return [
                'success' => true,
                'message' => 'Task cancelled successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task cancellation failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Task cancellation failed'
            ];
        }
    }

    /**
     * Update service provider statistics
     */
    private function updateServiceProviderStats(ServiceProvider $serviceProvider, string $action): void
    {
        switch ($action) {
            case 'completed':
                $serviceProvider->increment('tasks_completed');
                break;
            case 'cancelled':
                $serviceProvider->increment('tasks_cancelled');
                break;
            case 'rejected':
                $serviceProvider->increment('tasks_rejected');
                break;
        }

        // Recalculate acceptance rate
        $totalTasks = $serviceProvider->tasks_completed + $serviceProvider->tasks_cancelled + $serviceProvider->tasks_rejected;
        if ($totalTasks > 0) {
            $acceptanceRate = ($serviceProvider->tasks_completed / $totalTasks) * 100;
            $serviceProvider->update(['acceptance_rate' => round($acceptanceRate, 2)]);
        }
    }

    /**
     * Get task status history
     */
    public function getTaskStatusHistory(Task $task): array
    {
        $history = [];

        if ($task->created_at) {
            $history[] = [
                'status' => Task::STATUS_REQUESTED,
                'timestamp' => $task->created_at,
                'description' => 'Task requested by customer'
            ];
        }

        if ($task->assigned_at) {
            $history[] = [
                'status' => Task::STATUS_ASSIGNED,
                'timestamp' => $task->assigned_at,
                'description' => 'Task assigned to service provider'
            ];
        }

        if ($task->otp_start_verified_at) {
            $history[] = [
                'status' => Task::STATUS_OTP_START_VERIFIED,
                'timestamp' => $task->otp_start_verified_at,
                'description' => 'Start OTP verified'
            ];
        }

        if ($task->started_at) {
            $history[] = [
                'status' => Task::STATUS_STARTED,
                'timestamp' => $task->started_at,
                'description' => 'Task started'
            ];
        }

        if ($task->completed_at) {
            $history[] = [
                'status' => Task::STATUS_COMPLETED,
                'timestamp' => $task->completed_at,
                'description' => 'Task completed'
            ];
        }

        if ($task->cancelled_at) {
            $history[] = [
                'status' => 'cancelled',
                'timestamp' => $task->cancelled_at,
                'description' => 'Task cancelled: ' . $task->cancellation_reason
            ];
        }

        return $history;
    }

    /**
     * Get task progress percentage
     */
    public function getTaskProgress(Task $task): int
    {
        $statusProgress = [
            Task::STATUS_REQUESTED => 10,
            Task::STATUS_SEARCHING => 20,
            Task::STATUS_ASSIGNED => 30,
            Task::STATUS_ON_THE_WAY => 40,
            Task::STATUS_ARRIVED => 50,
            Task::STATUS_OTP_START_VERIFIED => 60,
            Task::STATUS_STARTED => 70,
            Task::STATUS_PAUSED => 70,
            Task::STATUS_RESUMED => 80,
            Task::STATUS_COMPLETED => 90,
            Task::STATUS_RATED => 100,
        ];

        return $statusProgress[$task->status] ?? 0;
    }

    /**
     * Check if task can be cancelled by user type
     */
    public function canCancelTask(Task $task, string $userType): bool
    {
        $cancellableStatuses = [
            'customer' => [
                Task::STATUS_REQUESTED,
                Task::STATUS_SEARCHING,
                Task::STATUS_ASSIGNED,
                Task::STATUS_ON_THE_WAY,
            ],
            'service_provider' => [
                Task::STATUS_ASSIGNED,
                Task::STATUS_ON_THE_WAY,
            ],
            'admin' => [
                Task::STATUS_REQUESTED,
                Task::STATUS_SEARCHING,
                Task::STATUS_ASSIGNED,
                Task::STATUS_ON_THE_WAY,
                Task::STATUS_ARRIVED,
                Task::STATUS_OTP_START_VERIFIED,
                Task::STATUS_STARTED,
                Task::STATUS_PAUSED,
                Task::STATUS_RESUMED,
            ]
        ];

        return in_array($task->status, $cancellableStatuses[$userType] ?? []);
    }
}