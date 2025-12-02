<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\SPPerformanceMetric;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocationEngineController extends Controller
{
    /**
     * Auto-assign task to best available service provider
     */
    public function autoAssignTask(Request $request)
    {
        $taskId = $request->get('task_id');
        
        if (!$taskId) {
            return response()->json([
                'success' => false,
                'message' => 'Task ID is required'
            ], 400);
        }

        try {
            $task = Task::with(['category', 'customerAddress'])->find($taskId);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            if ($task->status !== 'searching') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task is not available for assignment'
                ], 400);
            }

            // Find best service provider
            $bestSP = $this->findBestServiceProvider($task);
            
            if (!$bestSP) {
                return response()->json([
                    'success' => false,
                    'message' => 'No suitable service provider found',
                    'data' => [
                        'task_id' => $taskId,
                        'retry_after' => 300 // 5 minutes
                    ]
                ], 404);
            }

            // Assign task
            $task->update([
                'service_provider_id' => $bestSP->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'allocation_score' => $bestSP->allocation_score ?? 0
            ]);

            Log::info("Task {$taskId} auto-assigned to SP {$bestSP->id} with score {$bestSP->allocation_score}");

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully',
                'data' => [
                    'task_id' => $taskId,
                    'service_provider_id' => $bestSP->id,
                    'service_provider_name' => $bestSP->name,
                    'allocation_score' => $bestSP->allocation_score,
                    'estimated_arrival' => now()->addMinutes($bestSP->estimated_travel_time ?? 30)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Auto-assignment failed for task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Auto-assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available service providers for a task
     */
    public function getAvailableProviders(Request $request)
    {
        $taskId = $request->get('task_id');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');
        $categoryId = $request->get('category_id');
        
        if (!$taskId && (!$latitude || !$longitude || !$categoryId)) {
            return response()->json([
                'success' => false,
                'message' => 'Either task_id or (latitude, longitude, category_id) is required'
            ], 400);
        }

        try {
            if ($taskId) {
                $task = Task::with(['category', 'customerAddress'])->find($taskId);
                if (!$task) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Task not found'
                    ], 404);
                }
                $latitude = $task->customerAddress->latitude;
                $longitude = $task->customerAddress->longitude;
                $categoryId = $task->category_id;
            }

            $providers = $this->getFilteredProviders($latitude, $longitude, $categoryId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'providers' => $providers,
                    'total_count' => count($providers),
                    'search_radius' => '50km',
                    'location' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available providers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign task to different service provider
     */
    public function reassignTask(Request $request)
    {
        $taskId = $request->get('task_id');
        $newSPId = $request->get('new_service_provider_id');
        $reason = $request->get('reason', 'Manual reassignment');
        
        if (!$taskId || !$newSPId) {
            return response()->json([
                'success' => false,
                'message' => 'Task ID and new service provider ID are required'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $task = Task::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $newSP = ServiceProvider::find($newSPId);
            if (!$newSP || !$newSP->is_active || !$newSP->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service provider not available'
                ], 400);
            }

            $oldSPId = $task->service_provider_id;
            
            // Update task
            $task->update([
                'service_provider_id' => $newSPId,
                'status' => 'assigned',
                'assigned_at' => now(),
                'reassignment_reason' => $reason,
                'reassigned_from' => $oldSPId,
                'reassigned_at' => now()
            ]);

            DB::commit();

            Log::info("Task {$taskId} reassigned from SP {$oldSPId} to SP {$newSPId}. Reason: {$reason}");

            return response()->json([
                'success' => true,
                'message' => 'Task reassigned successfully',
                'data' => [
                    'task_id' => $taskId,
                    'old_service_provider_id' => $oldSPId,
                    'new_service_provider_id' => $newSPId,
                    'reason' => $reason
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Reassignment failed for task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Reassignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get allocation statistics
     */
    public function getAllocationStats(Request $request)
    {
        try {
            $period = $request->get('period', 'today'); // today, week, month
            
            $startDate = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => now()->startOfDay()
            };

            $stats = [
                'total_assignments' => Task::where('assigned_at', '>=', $startDate)->count(),
                'successful_assignments' => Task::where('assigned_at', '>=', $startDate)
                    ->whereNotNull('service_provider_id')
                    ->count(),
                'failed_assignments' => Task::where('created_at', '>=', $startDate)
                    ->where('status', 'searching')
                    ->where('created_at', '<', now()->subMinutes(30))
                    ->count(),
                'average_assignment_time' => Task::where('assigned_at', '>=', $startDate)
                    ->whereNotNull('service_provider_id')
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, assigned_at)) as avg_time')
                    ->value('avg_time'),
                'reassignments' => Task::where('reassigned_at', '>=', $startDate)->count(),
                'active_providers' => ServiceProvider::where('is_active', true)
                    ->where('is_available', true)
                    ->where('last_seen_at', '>=', now()->subHours(1))
                    ->count()
            ];

            // Get hourly breakdown
            $hourlyStats = Task::where('assigned_at', '>=', $startDate)
                ->selectRaw('HOUR(assigned_at) as hour, COUNT(*) as assignments')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'hourly_breakdown' => $hourlyStats,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch allocation statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find best service provider for a task using allocation algorithm
     */
    private function findBestServiceProvider(Task $task)
    {
        $customerLat = $task->customerAddress->latitude;
        $customerLng = $task->customerAddress->longitude;
        $categoryId = $task->category_id;

        // Get filtered providers
        $providers = $this->getFilteredProviders($customerLat, $customerLng, $categoryId);
        
        if (empty($providers)) {
            return null;
        }

        // Calculate allocation scores and find best match
        $bestProvider = null;
        $bestScore = 0;

        foreach ($providers as $provider) {
            $score = $this->calculateAllocationScore($provider, $task);
            $provider->allocation_score = $score;
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestProvider = $provider;
            }
        }

        return $bestProvider;
    }

    /**
     * Get filtered service providers based on location and category
     */
    private function getFilteredProviders($latitude, $longitude, $categoryId)
    {
        $maxDistance = PlatformSetting::getValue('max_assignment_distance', 50); // km
        
        return ServiceProvider::select([
            'service_providers.*',
            DB::raw("(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance")
        ])
        ->setBindings([$latitude, $longitude, $latitude])
        ->where('is_active', true)
        ->where('is_verified', true)
        ->where('is_available', true)
        ->where('last_seen_at', '>=', now()->subHours(2)) // Active in last 2 hours
        ->whereHas('categories', function($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->having('distance', '<=', $maxDistance)
        ->with(['performanceMetrics', 'spUser:id,first_name,last_name'])
        ->orderBy('distance')
        ->get()
        ->toArray();
    }

    /**
     * Calculate allocation score for a service provider
     */
    private function calculateAllocationScore($provider, Task $task)
    {
        $weights = [
            'distance' => 0.3,
            'rating' => 0.25,
            'completion_rate' => 0.2,
            'response_time' => 0.15,
            'availability' => 0.1
        ];

        $scores = [];

        // Distance score (closer is better, max 50km)
        $distance = $provider['distance'] ?? 50;
        $scores['distance'] = max(0, (50 - $distance) / 50 * 100);

        // Rating score
        $rating = $provider['performance_metrics']['average_rating'] ?? 0;
        $scores['rating'] = ($rating / 5) * 100;

        // Completion rate score
        $totalTasks = $provider['performance_metrics']['total_tasks'] ?? 0;
        $completedTasks = $provider['performance_metrics']['completed_tasks'] ?? 0;
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 50;
        $scores['completion_rate'] = $completionRate;

        // Response time score (faster is better, max 60 minutes)
        $responseTime = $provider['performance_metrics']['response_time_avg'] ?? 30;
        $scores['response_time'] = max(0, (60 - $responseTime) / 60 * 100);

        // Availability score (based on last seen and current load)
        $lastSeen = $provider['last_seen_at'] ?? now()->subHours(2);
        $minutesSinceLastSeen = now()->diffInMinutes($lastSeen);
        $availabilityScore = max(0, (120 - $minutesSinceLastSeen) / 120 * 100);
        
        // Reduce score if SP has too many active tasks
        $activeTasks = Task::where('service_provider_id', $provider['id'])
            ->whereIn('status', ['assigned', 'on_the_way', 'arrived', 'started'])
            ->count();
        
        if ($activeTasks >= 3) {
            $availabilityScore *= 0.5; // Reduce by 50% if overloaded
        }
        
        $scores['availability'] = $availabilityScore;

        // Calculate weighted final score
        $finalScore = 0;
        foreach ($weights as $factor => $weight) {
            $finalScore += ($scores[$factor] ?? 0) * $weight;
        }

        // Bonus for high performers
        if ($rating >= 4.5 && $completionRate >= 90) {
            $finalScore *= 1.1; // 10% bonus
        }

        // Penalty for poor performers
        if ($rating < 3.5 || $completionRate < 70) {
            $finalScore *= 0.8; // 20% penalty
        }

        return round($finalScore, 2);
    }
}
