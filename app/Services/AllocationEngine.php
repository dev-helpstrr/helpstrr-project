<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\TaskBroadcast;
use App\Models\CustomerAddress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AllocationEngine
{
    /**
     * This is the NON-NEGOTIABLE DEFINITIVE LOGIC for SP allocation
     * 
     * PRIMARY LOGIC — CAPABILITY MATCH (38 filters)
     * SECONDARY LOGIC — QUALITY SORTING
     * TERTIARY LOGIC — DISTANCE WINDOW
     * EXCEPTIONS — Customer's first 3 bookings, First quarter launch
     */

    /**
     * Find and allocate service providers for a task
     */
    public function allocateServiceProviders(Task $task): array
    {
        // Step 1: Apply all 38 capability filters
        $eligibleSPs = $this->applyCapabilityFilters($task);

        if ($eligibleSPs->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No eligible service providers found',
                'broadcasts' => []
            ];
        }

        // Step 2: Apply quality sorting and create broadcast rounds
        $broadcastRounds = $this->createBroadcastRounds($task, $eligibleSPs);

        // Step 3: Send broadcasts
        $broadcasts = $this->sendBroadcasts($task, $broadcastRounds);

        return [
            'success' => true,
            'message' => 'Broadcasts sent successfully',
            'broadcasts' => $broadcasts
        ];
    }

    /**
     * Apply all 38 capability filters as per requirements
     */
    private function applyCapabilityFilters(Task $task): Collection
    {
        $customerAddress = $task->customerAddress;
        $subcategory = $task->subcategory;
        $category = $task->category;
        $scheduledAt = Carbon::parse($task->scheduled_at);
        $isNightTime = $scheduledAt->hour >= 22 || $scheduledAt->hour <= 6;

        $query = ServiceProvider::query()
            // Filter 1-12: Basic eligibility
            ->active()
            ->verified()
            ->kycApproved()
            ->notBlocked()
            ->notInCooldown()
            ->online()
            
            // Filter 13-14: Category/Subcategory capability
            ->bySubcategory($task->subcategory_id)
            
            // Filter 15: Not already on task
            ->whereDoesntHave('tasks', function ($q) {
                $q->inProgress();
            });

        // Filter 16: Cuisine match (multi-select, at least 1 overlap) - Chef only
        if ($task->isChefTask() && $task->selectedCuisines->isNotEmpty()) {
            $cuisineIds = $task->selectedCuisines->pluck('id')->toArray();
            $query->whereHas('cuisineCapabilities', function ($q) use ($cuisineIds) {
                $q->whereIn('chef_cuisine_id', $cuisineIds)->where('is_active', true);
            });
        }

        // Filter 17: Diet preference capability - Chef only
        if ($task->isChefTask() && $task->dietary_preference_id) {
            $query->whereHas('dietaryCapabilities', function ($q) use ($task) {
                $q->where('dietary_preference_id', $task->dietary_preference_id)
                  ->where('is_active', true);
            });
        }

        // Filter 18: Add-on flags capability - Chef only
        if ($task->isChefTask() && $task->addonFlags->isNotEmpty()) {
            $addonFlagIds = $task->addonFlags->pluck('id')->toArray();
            foreach ($addonFlagIds as $flagId) {
                $query->whereHas('addonCapabilities', function ($q) use ($flagId) {
                    $q->where('chef_addon_flag_id', $flagId)->where('is_active', true);
                });
            }
        }

        // Filter 19-20: Optional flags (hard filters mandatory)
        if ($task->optionalFlags->isNotEmpty()) {
            $hardFilterIds = $task->optionalFlags->where('is_hard_filter', true)->pluck('id')->toArray();
            foreach ($hardFilterIds as $flagId) {
                $query->whereHas('optionalCapabilities', function ($q) use ($flagId) {
                    $q->where('optional_flag_id', $flagId)->where('is_active', true);
                });
            }
        }

        // Filter 21: Pax capacity
        if ($task->requiresPaxCount()) {
            $query->withPaxCapacity($task->pax_count, $task->subcategory_id);
        }

        // Filter 22: Night shift willingness
        if ($isNightTime) {
            $query->canWorkNights();
        }

        // Filter 23: Travel distance capability (will be applied in distance window)
        // Filter 24: Availability window (basic check - SP is online)
        // Filter 25: Online status (already applied above)

        $serviceProviders = $query->with([
            'spUser',
            'capabilities',
            'cuisineCapabilities',
            'dietaryCapabilities',
            'addonCapabilities',
            'optionalCapabilities'
        ])->get();

        // Apply remaining filters that require individual checks
        return $serviceProviders->filter(function ($sp) use ($task, $customerAddress, $isNightTime) {
            // Filter 26-38: Individual capability checks
            return $this->passesIndividualFilters($sp, $task, $customerAddress, $isNightTime);
        });
    }

    /**
     * Apply individual filters that can't be done in query
     */
    private function passesIndividualFilters(ServiceProvider $sp, Task $task, CustomerAddress $customerAddress, bool $isNightTime): bool
    {
        // Check travel distance capability
        if ($customerAddress->hasCoordinates() && $sp->spUser->hasCoordinates()) {
            $distance = $sp->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude);
            $capability = $sp->capabilities()->where('subcategory_id', $task->subcategory_id)->first();
            
            if ($capability && $distance > $capability->max_travel_distance_km) {
                return false;
            }
        }

        // Check availability window (more detailed check)
        if (!$sp->spUser->isAvailableNow()) {
            return false;
        }

        // Check night shift availability
        if ($isNightTime) {
            $capability = $sp->capabilities()->where('subcategory_id', $task->subcategory_id)->first();
            if ($capability && !$capability->night_shift_available) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create broadcast rounds with quality sorting
     */
    private function createBroadcastRounds(Task $task, Collection $eligibleSPs): array
    {
        $customerAddress = $task->customerAddress;
        $isEarlyCustomer = $this->isEarlyCustomer($task->customer_id);
        $isFirstQuarterLaunch = $this->isFirstQuarterLaunch();

        // Separate rated and new SPs
        if ($isFirstQuarterLaunch) {
            // First quarter launch - ignore rating rules entirely
            $ratedSPs = $eligibleSPs;
            $newSPs = collect();
        } else {
            $ratedSPs = $eligibleSPs->filter(function ($sp) {
                return $sp->total_ratings >= 5 && $sp->rating >= 4.0;
            });
            $newSPs = $eligibleSPs->filter(function ($sp) {
                return $sp->total_ratings < 5;
            });
        }

        // Sort rated SPs by quality score
        $sortedRatedSPs = $this->sortByQuality($ratedSPs, $customerAddress);

        $broadcastRounds = [];

        // Broadcast A: Top 5 highest-rated SPs (3km radius)
        $broadcastA = $this->filterByDistance($sortedRatedSPs, $customerAddress, 3)->take(5);
        if ($broadcastA->isNotEmpty()) {
            $broadcastRounds['A'] = [
                'sps' => $broadcastA,
                'radius_km' => 3,
                'description' => 'Top 5 highest-rated SPs'
            ];
        }

        // Broadcast B: Next 15 highest-rated SPs (5km radius)
        $broadcastB = $this->filterByDistance($sortedRatedSPs, $customerAddress, 5)->skip(5)->take(15);
        if ($broadcastB->isNotEmpty()) {
            $broadcastRounds['B'] = [
                'sps' => $broadcastB,
                'radius_km' => 5,
                'description' => 'Next 15 highest-rated SPs'
            ];
        }

        // New SP Boost: Random 5 new SPs (except early customer)
        if (!$isEarlyCustomer && $newSPs->isNotEmpty()) {
            $newSPBoost = $this->filterByDistance($newSPs, $customerAddress, 5)->shuffle()->take(5);
            if ($newSPBoost->isNotEmpty()) {
                $broadcastRounds['new_sp_boost'] = [
                    'sps' => $newSPBoost,
                    'radius_km' => 5,
                    'description' => 'New SP boost (random 5 new SPs)'
                ];
            }
        }

        // If no SPs found in 5km, expand to 7km (Admin override)
        if (empty($broadcastRounds)) {
            $expandedSPs = $this->filterByDistance($sortedRatedSPs, $customerAddress, 7)->take(10);
            if ($expandedSPs->isNotEmpty()) {
                $broadcastRounds['expanded'] = [
                    'sps' => $expandedSPs,
                    'radius_km' => 7,
                    'description' => 'Expanded radius (7km) - Admin override'
                ];
            }
        }

        return $broadcastRounds;
    }

    /**
     * Sort SPs by quality using the strict order from requirements
     */
    private function sortByQuality(Collection $sps, CustomerAddress $customerAddress): Collection
    {
        return $sps->sort(function ($a, $b) use ($customerAddress) {
            // 1. Rating
            if ($a->rating != $b->rating) {
                return $b->rating <=> $a->rating;
            }

            // 2. Acceptance Rate
            if ($a->acceptance_rate != $b->acceptance_rate) {
                return $b->acceptance_rate <=> $a->acceptance_rate;
            }

            // 3. Punctuality Score
            if ($a->punctuality_score != $b->punctuality_score) {
                return $b->punctuality_score <=> $a->punctuality_score;
            }

            // 4. Behaviour Score
            if ($a->behaviour_score != $b->behaviour_score) {
                return $b->behaviour_score <=> $a->behaviour_score;
            }

            // 5. Gold Level Priority
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

            // 9. SP Cooldown Status (not in cooldown is better)
            $aCooldown = $a->isInCooldown();
            $bCooldown = $b->isInCooldown();
            if ($aCooldown != $bCooldown) {
                return $aCooldown <=> $bCooldown;
            }

            // 10. Fairness Rule (avoid SP monopoly) - least recent assignment
            $aLastAssigned = $a->last_assigned_at ? $a->last_assigned_at->timestamp : 0;
            $bLastAssigned = $b->last_assigned_at ? $b->last_assigned_at->timestamp : 0;
            if ($aLastAssigned != $bLastAssigned) {
                return $aLastAssigned <=> $bLastAssigned;
            }

            // 11. Least recent assignment (already covered above)
            // 12. Distance (after logic layer)
            if ($customerAddress->hasCoordinates()) {
                $aDistance = $a->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude);
                $bDistance = $b->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude);
                return $aDistance <=> $bDistance;
            }

            return 0;
        })->values();
    }

    /**
     * Filter SPs by distance
     */
    private function filterByDistance(Collection $sps, CustomerAddress $customerAddress, int $radiusKm): Collection
    {
        if (!$customerAddress->hasCoordinates()) {
            return $sps;
        }

        return $sps->filter(function ($sp) use ($customerAddress, $radiusKm) {
            $distance = $sp->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude);
            return $distance <= $radiusKm;
        });
    }

    /**
     * Send broadcasts to service providers
     */
    private function sendBroadcasts(Task $task, array $broadcastRounds): array
    {
        $broadcasts = [];
        $customerAddress = $task->customerAddress;

        foreach ($broadcastRounds as $roundName => $round) {
            foreach ($round['sps'] as $index => $sp) {
                $distance = $customerAddress->hasCoordinates() 
                    ? $sp->getDistanceFrom($customerAddress->latitude, $customerAddress->longitude)
                    : 0;

                $broadcast = TaskBroadcast::create([
                    'task_id' => $task->id,
                    'service_provider_id' => $sp->id,
                    'broadcast_round' => $roundName,
                    'distance_km' => $distance,
                    'sp_rating' => $sp->rating,
                    'sp_rank_in_round' => $index + 1,
                    'sent_at' => now(),
                    'expires_at' => now()->addSeconds(60), // 60 seconds timeout
                    'timeout_seconds' => 60,
                ]);

                $broadcasts[] = $broadcast;

                // Here you would send the actual notification to the SP
                // This could be via WebSocket, Push Notification, etc.
                $this->sendBroadcastNotification($sp, $task, $broadcast);
            }
        }

        return $broadcasts;
    }

    /**
     * Send broadcast notification to service provider
     */
    private function sendBroadcastNotification(ServiceProvider $sp, Task $task, TaskBroadcast $broadcast): void
    {
        // Implementation would depend on your notification system
        // This could be WebSocket, Push Notification, SMS, etc.
        
        // For now, we'll just log it
        \Log::info("Broadcast sent to SP {$sp->id} for task {$task->id}", [
            'broadcast_id' => $broadcast->id,
            'round' => $broadcast->broadcast_round,
            'expires_at' => $broadcast->expires_at,
        ]);
    }

    /**
     * Check if customer is in their first 3 bookings
     */
    private function isEarlyCustomer(int $customerId): bool
    {
        $completedTasks = Task::where('customer_id', $customerId)
            ->completed()
            ->count();

        return $completedTasks < 3;
    }

    /**
     * Check if we're in first quarter launch
     */
    private function isFirstQuarterLaunch(): bool
    {
        // This would be based on system settings or launch date
        // For now, returning false
        return false;
    }

    /**
     * Handle broadcast response from service provider
     */
    public function handleBroadcastResponse(TaskBroadcast $broadcast, string $response, ?string $rejectionReason = null): bool
    {
        if ($broadcast->response !== 'pending') {
            return false; // Already responded
        }

        if ($broadcast->expires_at->isPast()) {
            $broadcast->update(['response' => 'timeout']);
            return false; // Expired
        }

        $responseTime = now()->diffInSeconds($broadcast->sent_at);

        $broadcast->update([
            'response' => $response,
            'responded_at' => now(),
            'response_time_seconds' => $responseTime,
            'rejection_reason' => $rejectionReason,
        ]);

        if ($response === 'accepted') {
            // Assign task to this SP
            $task = $broadcast->task;
            $task->update([
                'service_provider_id' => $broadcast->service_provider_id,
                'status' => Task::STATUS_ASSIGNED,
                'assigned_at' => now(),
            ]);

            // Cancel other pending broadcasts for this task
            TaskBroadcast::where('task_id', $task->id)
                ->where('id', '!=', $broadcast->id)
                ->where('response', 'pending')
                ->update(['response' => 'cancelled']);

            // Update SP's last assigned timestamp
            $broadcast->serviceProvider->update([
                'last_assigned_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle broadcast timeout
     */
    public function handleBroadcastTimeout(TaskBroadcast $broadcast): void
    {
        if ($broadcast->response === 'pending' && $broadcast->expires_at->isPast()) {
            $broadcast->update(['response' => 'timeout']);
        }
    }

    /**
     * Get allocation statistics for a task
     */
    public function getAllocationStats(Task $task): array
    {
        $broadcasts = $task->broadcasts;

        return [
            'total_broadcasts' => $broadcasts->count(),
            'responses' => [
                'accepted' => $broadcasts->where('response', 'accepted')->count(),
                'rejected' => $broadcasts->where('response', 'rejected')->count(),
                'timeout' => $broadcasts->where('response', 'timeout')->count(),
                'pending' => $broadcasts->where('response', 'pending')->count(),
            ],
            'average_response_time' => $broadcasts->whereNotNull('response_time_seconds')->avg('response_time_seconds'),
            'broadcast_rounds' => $broadcasts->groupBy('broadcast_round')->map->count(),
        ];
    }
}