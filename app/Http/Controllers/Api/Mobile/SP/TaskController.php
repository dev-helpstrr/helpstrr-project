<?php

namespace App\Http\Controllers\Api\Mobile\SP;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\SPPerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Get SP dashboard data
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        try {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            // Get task statistics
            $stats = [
                'pending_requests' => Task::where('service_provider_id', $serviceProvider->id)
                    ->where('status', 'assigned')
                    ->count(),
                    
                'active_tasks' => Task::where('service_provider_id', $serviceProvider->id)
                    ->whereIn('status', ['on_the_way', 'arrived', 'started'])
                    ->count(),
                    
                'completed_today' => Task::where('service_provider_id', $serviceProvider->id)
                    ->where('status', 'completed')
                    ->whereDate('completed_at', $today)
                    ->count(),
                    
                'earnings_today' => Task::where('service_provider_id', $serviceProvider->id)
                    ->where('status', 'completed')
                    ->whereDate('completed_at', $today)
                    ->sum('sp_amount'),
                    
                'earnings_this_month' => Task::where('service_provider_id', $serviceProvider->id)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', $thisMonth)
                    ->sum('sp_amount'),
                    
                'average_rating' => $serviceProvider->performanceMetrics?->average_rating ?? 0,
                'total_tasks' => $serviceProvider->performanceMetrics?->total_tasks ?? 0,
                'completion_rate' => $serviceProvider->performanceMetrics?->total_tasks > 0 ? 
                    round(($serviceProvider->performanceMetrics->completed_tasks / $serviceProvider->performanceMetrics->total_tasks) * 100, 1) : 0
            ];

            // Get recent tasks
            $recentTasks = Task::where('service_provider_id', $serviceProvider->id)
                ->with(['customer:id,name,phone', 'subcategory:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get pending task requests
            $pendingRequests = Task::where('service_provider_id', $serviceProvider->id)
                ->where('status', 'assigned')
                ->with(['customer:id,name,phone', 'subcategory:id,name', 'customerAddress'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_tasks' => $recentTasks,
                    'pending_requests' => $pendingRequests,
                    'service_provider' => $serviceProvider->only(['id', 'name', 'is_active', 'is_verified', 'verification_status'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task requests for SP
     */
    public function getTaskRequests(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        try {
            $status = $request->get('status', 'assigned');
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $tasks = Task::where('service_provider_id', $serviceProvider->id)
                ->when($status, function($query) use ($status) {
                    if ($status === 'active') {
                        $query->whereIn('status', ['assigned', 'on_the_way', 'arrived', 'started']);
                    } else {
                        $query->where('status', $status);
                    }
                })
                ->with([
                    'customer:id,name,phone',
                    'subcategory:id,name',
                    'customerAddress',
                    'category:id,name'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $tasks->items(),
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept task request
     */
    public function acceptTask(Request $request, $taskId)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        try {
            $task = Task::where('id', $taskId)
                ->where('service_provider_id', $serviceProvider->id)
                ->where('status', 'assigned')
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or already processed'
                ], 404);
            }

            $task->update([
                'status' => 'on_the_way',
                'accepted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task accepted successfully',
                'data' => [
                    'task' => $task->load(['customer:id,name,phone', 'subcategory:id,name', 'customerAddress'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject task request
     */
    public function rejectTask(Request $request, $taskId)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::where('id', $taskId)
                ->where('service_provider_id', $serviceProvider->id)
                ->where('status', 'assigned')
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or already processed'
                ], 404);
            }

            $task->update([
                'status' => 'searching', // Back to searching for another SP
                'service_provider_id' => null,
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task rejected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task status (travel, arrival, start, complete)
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:on_the_way,arrived,started,completed',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'notes' => 'sometimes|string|max:500',
            'completion_images' => 'sometimes|array',
            'completion_images.*' => 'image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::where('id', $taskId)
                ->where('service_provider_id', $serviceProvider->id)
                ->whereIn('status', ['on_the_way', 'arrived', 'started'])
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or cannot be updated'
                ], 404);
            }

            $updateData = [
                'status' => $request->status
            ];

            // Add location if provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $updateData['current_latitude'] = $request->latitude;
                $updateData['current_longitude'] = $request->longitude;
            }

            // Handle status-specific updates
            switch ($request->status) {
                case 'arrived':
                    $updateData['arrived_at'] = now();
                    break;
                case 'started':
                    $updateData['started_at'] = now();
                    break;
                case 'completed':
                    $updateData['completed_at'] = now();
                    $updateData['notes'] = $request->notes;
                    
                    // Handle completion images
                    if ($request->hasFile('completion_images')) {
                        $images = [];
                        foreach ($request->file('completion_images') as $image) {
                            $images[] = $image->store('task-completions', 'private');
                        }
                        $updateData['completion_images'] = $images;
                    }
                    
                    // Update performance metrics
                    $this->updatePerformanceMetrics($serviceProvider, $task);
                    break;
            }

            $task->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => [
                    'task' => $task->fresh()->load(['customer:id,name,phone', 'subcategory:id,name'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task details
     */
    public function getTaskDetails(Request $request, $taskId)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        try {
            $task = Task::where('id', $taskId)
                ->where('service_provider_id', $serviceProvider->id)
                ->with([
                    'customer:id,name,phone,email',
                    'subcategory:id,name,description',
                    'category:id,name',
                    'customerAddress'
                ])
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SP earnings
     */
    public function getEarnings(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            
            $startDate = match($period) {
                'day' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth()
            };

            $earnings = Task::where('service_provider_id', $serviceProvider->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(sp_amount) as total_earnings,
                    AVG(sp_amount) as avg_earnings_per_task,
                    SUM(platform_fee) as total_platform_fees
                ')
                ->first();

            // Get daily breakdown for charts
            $dailyEarnings = Task::where('service_provider_id', $serviceProvider->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->selectRaw('DATE(completed_at) as date, SUM(sp_amount) as earnings, COUNT(*) as tasks')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $earnings,
                    'daily_breakdown' => $dailyEarnings,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch earnings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update SP availability status
     */
    public function updateAvailability(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_available' => 'required|boolean',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'is_available' => $request->is_available,
                'last_seen_at' => now()
            ];

            if ($request->has('latitude') && $request->has('longitude')) {
                $updateData['latitude'] = $request->latitude;
                $updateData['longitude'] = $request->longitude;
            }

            $serviceProvider->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Availability updated successfully',
                'data' => [
                    'is_available' => $serviceProvider->is_available,
                    'last_seen_at' => $serviceProvider->last_seen_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update performance metrics after task completion
     */
    private function updatePerformanceMetrics(ServiceProvider $serviceProvider, Task $task)
    {
        $metrics = $serviceProvider->performanceMetrics;
        
        if (!$metrics) {
            $metrics = SPPerformanceMetric::create([
                'service_provider_id' => $serviceProvider->id,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'cancelled_tasks' => 0,
                'average_rating' => 0,
                'total_earnings' => 0,
                'punctuality_score' => 0,
                'quality_score' => 0,
                'response_time_avg' => 0,
                'complaints_count' => 0,
                'last_updated' => now()
            ]);
        }

        // Calculate punctuality (if task was completed on time)
        $isPunctual = $task->completed_at <= $task->scheduled_at->addMinutes(30); // 30 min grace period
        
        $metrics->increment('completed_tasks');
        $metrics->increment('total_earnings', $task->sp_amount);
        
        // Update punctuality score
        $totalCompleted = $metrics->completed_tasks;
        $currentPunctualityScore = $metrics->punctuality_score;
        $newPunctualityScore = (($currentPunctualityScore * ($totalCompleted - 1)) + ($isPunctual ? 100 : 0)) / $totalCompleted;
        
        $metrics->update([
            'punctuality_score' => $newPunctualityScore,
            'last_updated' => now()
        ]);
    }
}
