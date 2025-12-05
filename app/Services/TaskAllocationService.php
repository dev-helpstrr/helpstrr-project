<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\TaskBroadcast;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskAllocationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Initiate task allocation process
     * 
     * @param Task $task
     * @return bool
     */
    public function initiateAllocation(Task $task): bool
    {
        try {
            Log::info("Starting allocation for task: {$task->task_number}");

            // Find suitable service providers
            $suitableProviders = $this->findSuitableProviders($task);

            if ($suitableProviders->isEmpty()) {
                Log::warning("No suitable providers found for task: {$task->task_number}");
                $task->update(['status' => 'no_providers_available']);
                return false;
            }

            // Rank providers by priority
            $rankedProviders = $this->rankProviders($suitableProviders, $task);

            // Start broadcasting to providers
            $this->broadcastToProviders($task, $rankedProviders);

            Log::info("Task allocation initiated for task: {$task->task_number} with {$rankedProviders->count()} providers");
            return true;

        } catch (\Exception $e) {
            Log::error("Task allocation failed for task: {$task->task_number}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find suitable service providers for a task
     * 
     * @param Task $task
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findSuitableProviders(Task $task): \Illuminate\Database\Eloquent\Collection
    {
        $customerAddress = $task->customerAddress;
        $maxRadius = 10; // 10 km radius

        $query = ServiceProvider::with(['spUser', 'capabilities'])
            ->active()
            ->verified()
            ->kycApproved()
            ->notBlocked()
            ->notInCooldown()
            ->online();

        // Filter by category capability
        $query->byCategory($task->category_id);

        // Filter by subcategory capability
        $query->bySubcategory($task->subcategory_id);

        // Filter by location (within radius)
        $query->withinRadius(
            $customerAddress->latitude,
            $customerAddress->longitude,
            $maxRadius
        );

        // Filter by pax capacity (for chef services)
        if ($task->pax_count > 1) {
            $query->withPaxCapacity($task->pax_count, $task->subcategory_id);
        }

        // Filter by night work capability (if scheduled during night hours)
        $scheduledHour = Carbon::parse($task->scheduled_at)->hour;
        if ($scheduledHour >= 22 || $scheduledHour <= 6) {
            $query->canWorkNights();
        }

        // Additional filters for chef tasks
        if ($task->isChefTask()) {
            $providers = $query->get();
            
            // Filter by cuisine capabilities
            if ($task->selectedCuisines->isNotEmpty()) {
                $cuisineIds = $task->selectedCuisines->pluck('id')->toArray();
                $providers = $providers->filter(function ($provider) use ($cuisineIds) {
                    return $provider->canHandleCuisines($cuisineIds);
                });
            }

            // Filter by dietary preference
            if ($task->dietary_preference_id) {
                $providers = $providers->filter(function ($provider) use ($task) {
                    return $provider->canHandleDietaryPreference($task->dietary_preference_id);
                });
            }

            // Filter by addon flags
            if ($task->addonFlags->isNotEmpty()) {
                $addonFlagIds = $task->addonFlags->pluck('id')->toArray();
                $providers = $providers->filter(function ($provider) use ($addonFlagIds) {
                    return $provider->canHandleAddonFlags($addonFlagIds);
                });
            }

            // Filter by optional flags
            if ($task->optionalFlags->isNotEmpty()) {
                $optionalFlagIds = $task->optionalFlags->pluck('id')->toArray();
                $providers = $providers->filter(function ($provider) use ($optionalFlagIds) {
                    return $provider->canHandleOptionalFlags($optionalFlagIds);
                });
            }

            return collect($providers);
        }

        return $query->get();
    }

    /**
     * Rank service providers by priority
     * 
     * @param \Illuminate\Database\Eloquent\Collection $providers
     * @param Task $task
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function rankProviders($providers, Task $task): \Illuminate\Database\Eloquent\Collection
    {
        $customerAddress = $task->customerAddress;

        return $providers->map(function ($provider) use ($customerAddress) {
            // Calculate distance
            $distance = $provider->getDistanceFrom(
                $customerAddress->latitude,
                $customerAddress->longitude
            );

            // Calculate priority score
            $priorityScore = $this->calculatePriorityScore($provider, $distance);

            $provider->distance = $distance;
            $provider->priority_score = $priorityScore;

            return $provider;
        })->sortByDesc('priority_score');
    }

    /**
     * Calculate priority score for a service provider
     * 
     * @param ServiceProvider $provider
     * @param float $distance
     * @return float
     */
    private function calculatePriorityScore(ServiceProvider $provider, float $distance): float
    {
        $score = 0;

        // Distance score (closer is better) - 30% weight
        $distanceScore = max(0, 100 - ($distance * 10)); // Decrease by 10 points per km
        $score += $distanceScore * 0.3;

        // Rating score - 25% weight
        $ratingScore = ($provider->rating / 5) * 100;
        $score += $ratingScore * 0.25;

        // Acceptance rate score - 20% weight
        $score += $provider->acceptance_rate * 0.2;

        // Punctuality score - 15% weight
        $score += $provider->punctuality_score * 0.15;

        // Behaviour score - 10% weight
        $score += $provider->behaviour_score * 0.1;

        // Gold level bonus
        if ($provider->is_gold_level) {
            $score += 10;
        }

        // Recent activity bonus (if completed task recently)
        if ($provider->last_assigned_at && $provider->last_assigned_at->isAfter(now()->subDays(7))) {
            $score += 5;
        }

        // Penalty for recent cancellations
        $score -= $provider->cancellation_score * 0.5;

        // Penalty for complaints
        $score -= $provider->complaint_score * 0.3;

        return max(0, $score);
    }

    /**
     * Broadcast task to service providers
     * 
     * @param Task $task
     * @param \Illuminate\Database\Eloquent\Collection $providers
     * @return void
     */
    private function broadcastToProviders(Task $task, $providers): void
    {
        $broadcastBatchSize = 3; // Send to 3 providers at a time
        $broadcastTimeout = 60; // 60 seconds timeout per batch

        $providerBatches = $providers->chunk($broadcastBatchSize);

        foreach ($providerBatches as $batchIndex => $batch) {
            $expiresAt = now()->addSeconds($broadcastTimeout * ($batchIndex + 1));

            foreach ($batch as $provider) {
                $this->createTaskBroadcast($task, $provider, $expiresAt);
            }

            // Send notifications to this batch
            $this->notificationService->sendTaskBroadcastNotifications($task, $batch);

            // If this is not the last batch, we'll wait for responses before sending to next batch
            // This would be handled by a job queue in production
        }
    }

    /**
     * Create task broadcast record
     * 
     * @param Task $task
     * @param ServiceProvider $provider
     * @param Carbon $expiresAt
     * @return TaskBroadcast
     */
    private function createTaskBroadcast(Task $task, ServiceProvider $provider, Carbon $expiresAt): TaskBroadcast
    {
        return TaskBroadcast::create([
            'task_id' => $task->id,
            'service_provider_id' => $provider->id,
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'distance_km' => $provider->distance ?? 0,
            'priority_score' => $provider->priority_score ?? 0,
        ]);
    }

    /**
     * Handle task broadcast expiry
     * 
     * @param TaskBroadcast $broadcast
     * @return void
     */
    public function handleBroadcastExpiry(TaskBroadcast $broadcast): void
    {
        if ($broadcast->status === 'pending') {
            $broadcast->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);

            // Check if we need to send to next batch or mark task as unassigned
            $this->checkTaskAllocationStatus($broadcast->task);
        }
    }

    /**
     * Check task allocation status and take appropriate action
     * 
     * @param Task $task
     * @return void
     */
    private function checkTaskAllocationStatus(Task $task): void
    {
        $pendingBroadcasts = TaskBroadcast::where('task_id', $task->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->count();

        $acceptedBroadcasts = TaskBroadcast::where('task_id', $task->id)
            ->where('status', 'accepted')
            ->count();

        // If no pending broadcasts and no accepted broadcasts
        if ($pendingBroadcasts === 0 && $acceptedBroadcasts === 0) {
            // Try to find more providers or mark as unassigned
            $this->handleUnassignedTask($task);
        }
    }

    /**
     * Handle unassigned task
     * 
     * @param Task $task
     * @return void
     */
    private function handleUnassignedTask(Task $task): void
    {
        // Expand search radius and try again
        $this->expandSearchAndRetry($task);
    }

    /**
     * Expand search radius and retry allocation
     * 
     * @param Task $task
     * @return void
     */
    private function expandSearchAndRetry(Task $task): void
    {
        // Increase search radius to 15km and try again
        $customerAddress = $task->customerAddress;
        $expandedRadius = 15;

        $expandedProviders = ServiceProvider::with(['spUser', 'capabilities'])
            ->active()
            ->verified()
            ->kycApproved()
            ->notBlocked()
            ->notInCooldown()
            ->online()
            ->byCategory($task->category_id)
            ->bySubcategory($task->subcategory_id)
            ->withinRadius(
                $customerAddress->latitude,
                $customerAddress->longitude,
                $expandedRadius
            )
            ->get();

        // Exclude providers who already rejected this task
        $rejectedProviderIds = TaskBroadcast::where('task_id', $task->id)
            ->whereIn('status', ['rejected', 'expired'])
            ->pluck('service_provider_id')
            ->toArray();

        $expandedProviders = $expandedProviders->whereNotIn('id', $rejectedProviderIds);

        if ($expandedProviders->isNotEmpty()) {
            $rankedProviders = $this->rankProviders($expandedProviders, $task);
            $this->broadcastToProviders($task, $rankedProviders->take(5)); // Try with top 5
        } else {
            // No providers available, mark task accordingly
            $task->update(['status' => 'no_providers_available']);
            $this->notificationService->sendNoProvidersNotification($task);
        }
    }

    /**
     * Initiate task reallocation (when SP cancels)
     * 
     * @param Task $task
     * @return bool
     */
    public function initiateReallocation(Task $task): bool
    {
        try {
            Log::info("Starting reallocation for task: {$task->task_number}");

            // Reset task status
            $task->update([
                'status' => Task::STATUS_SEARCHING,
                'service_provider_id' => null,
                'assigned_at' => null,
            ]);

            // Find new providers (excluding the one who cancelled)
            $cancelledProviderId = $task->getOriginal('service_provider_id');
            
            $suitableProviders = $this->findSuitableProviders($task)
                ->where('id', '!=', $cancelledProviderId);

            if ($suitableProviders->isEmpty()) {
                $task->update(['status' => 'no_providers_available']);
                return false;
            }

            $rankedProviders = $this->rankProviders($suitableProviders, $task);
            $this->broadcastToProviders($task, $rankedProviders);

            Log::info("Task reallocation initiated for task: {$task->task_number}");
            return true;

        } catch (\Exception $e) {
            Log::error("Task reallocation failed for task: {$task->task_number}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get allocation statistics
     * 
     * @return array
     */
    public function getAllocationStatistics(): array
    {
        $today = today();
        
        return [
            'today' => [
                'total_tasks' => Task::whereDate('created_at', $today)->count(),
                'assigned_tasks' => Task::whereDate('created_at', $today)
                    ->whereNotNull('service_provider_id')->count(),
                'unassigned_tasks' => Task::whereDate('created_at', $today)
                    ->where('status', 'no_providers_available')->count(),
                'average_assignment_time' => $this->calculateAverageAssignmentTime($today),
            ],
            'this_week' => [
                'total_tasks' => Task::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'assignment_success_rate' => $this->calculateAssignmentSuccessRate(now()->startOfWeek(), now()->endOfWeek()),
            ],
            'active_providers' => ServiceProvider::active()->online()->count(),
            'providers_in_cooldown' => ServiceProvider::whereNotNull('cooldown_until')
                ->where('cooldown_until', '>', now())->count(),
        ];
    }

    /**
     * Calculate average assignment time
     * 
     * @param Carbon $date
     * @return float
     */
    private function calculateAverageAssignmentTime(Carbon $date): float
    {
        $tasks = Task::whereDate('created_at', $date)
            ->whereNotNull('assigned_at')
            ->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalSeconds = $tasks->sum(function ($task) {
            return $task->created_at->diffInSeconds($task->assigned_at);
        });

        return $totalSeconds / $tasks->count() / 60; // Return in minutes
    }

    /**
     * Calculate assignment success rate
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateAssignmentSuccessRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalTasks = Task::whereBetween('created_at', [$startDate, $endDate])->count();
        
        if ($totalTasks === 0) {
            return 0;
        }

        $assignedTasks = Task::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('service_provider_id')->count();

        return ($assignedTasks / $totalTasks) * 100;
    }
}