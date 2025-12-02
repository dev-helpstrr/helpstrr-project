<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Customer;
use App\Services\TaskLifecycleService;
use App\Services\ChefBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    protected TaskLifecycleService $taskLifecycleService;
    protected ChefBookingService $chefBookingService;

    public function __construct(TaskLifecycleService $taskLifecycleService, ChefBookingService $chefBookingService)
    {
        $this->taskLifecycleService = $taskLifecycleService;
        $this->chefBookingService = $chefBookingService;
    }

    /**
     * Get customer's tasks
     */
    public function index(Request $request): JsonResponse
    {
        $customer = Auth::user();
        
        $query = Task::where('customer_id', $customer->id)
            ->with(['category', 'subcategory', 'serviceProvider.spUser', 'customerAddress'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('scheduled_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('scheduled_at', '<=', $request->to_date);
        }

        $tasks = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }

    /**
     * Create a new task
     */
    public function store(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'requested_hours' => 'required|integer|min:1|max:12',
            'scheduled_at' => 'required|date|after:now',
            'pax_count' => 'nullable|integer|min:1|max:50',
            'recurrence_type' => 'in:one_time,two_days,three_days',
            'dietary_preference_id' => 'nullable|exists:dietary_preferences,id',
            'selected_cuisines' => 'nullable|array',
            'selected_cuisines.*' => 'exists:chef_cuisines,id',
            'addon_flags' => 'nullable|array',
            'addon_flags.*' => 'exists:chef_addon_flags,id',
            'optional_flags' => 'nullable|array',
            'optional_flags.*' => 'exists:optional_flags,id',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $taskData = array_merge($validator->validated(), [
            'customer_id' => $customer->id,
        ]);

        // For chef bookings, validate the complete flow
        if ($request->category_id && \App\Models\Category::find($request->category_id)->slug === 'chef') {
            $chefValidation = $this->chefBookingService->validateChefBooking($taskData);
            if (!$chefValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef booking validation failed',
                    'errors' => $chefValidation['errors']
                ], 422);
            }
        }

        $result = $this->taskLifecycleService->createTask($taskData);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => [
                'task' => $result['task'],
                'pricing' => $result['pricing']
            ]
        ], 201);
    }

    /**
     * Get task details
     */
    public function show(int $id): JsonResponse
    {
        $customer = Auth::user();
        
        $task = Task::where('customer_id', $customer->id)
            ->with([
                'category', 
                'subcategory', 
                'serviceProvider.spUser', 
                'customerAddress',
                'priceComponents',
                'selectedCuisines',
                'addonFlags',
                'optionalFlags',
                'dietaryPreference'
            ])
            ->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        // Get task status history
        $statusHistory = $this->taskLifecycleService->getTaskStatusHistory($task);
        
        // Get task progress
        $progress = $this->taskLifecycleService->getTaskProgress($task);

        return response()->json([
            'success' => true,
            'data' => [
                'task' => $task,
                'status_history' => $statusHistory,
                'progress_percentage' => $progress,
                'can_cancel' => $this->taskLifecycleService->canCancelTask($task, 'customer'),
                'can_rate' => $task->status === Task::STATUS_COMPLETED,
            ]
        ]);
    }

    /**
     * Cancel a task
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $customer = Auth::user();
        
        $task = Task::where('customer_id', $customer->id)->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        if (!$this->taskLifecycleService->canCancelTask($task, 'customer')) {
            return response()->json([
                'success' => false,
                'message' => 'Task cannot be cancelled at this stage'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskLifecycleService->cancelTask($task, $request->reason, 'customer');

        return response()->json($result);
    }

    /**
     * Rate a completed task
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $customer = Auth::user();
        
        $task = Task::where('customer_id', $customer->id)->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        if ($task->status !== Task::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Task must be completed before rating'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->taskLifecycleService->rateByCustomer($task, $request->rating, $request->feedback);

        return response()->json([
            'success' => true,
            'message' => 'Task rated successfully'
        ]);
    }

    /**
     * Get pricing preview for a task
     */
    public function pricingPreview(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'requested_hours' => 'required|integer|min:1|max:12',
            'scheduled_at' => 'required|date|after:now',
            'pax_count' => 'nullable|integer|min:1|max:50',
            'selected_cuisines' => 'nullable|array',
            'optional_flags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $taskData = array_merge($validator->validated(), [
            'customer_id' => $customer->id,
        ]);

        // For chef bookings, use the chef booking service
        if (\App\Models\Category::find($request->category_id)->slug === 'chef') {
            $result = $this->chefBookingService->getPricingPreview($taskData);
        } else {
            // For other categories, use the pricing engine directly
            try {
                $pricingEngine = app(\App\Services\PricingEngine::class);
                $pricingData = $pricingEngine->calculateTaskPricing($taskData);
                $customerPrice = $pricingEngine->getCustomerDisplayPrice($pricingData);

                $result = [
                    'success' => true,
                    'pricing' => $customerPrice,
                    'breakdown' => [
                        'base_amount' => $pricingData['base_amount'],
                        'billable_hours' => $pricingData['billable_hours'],
                        'hourly_rate' => $pricingData['hourly_rate'],
                        'has_consultation_fee' => $pricingData['consultation_fee'] > 0,
                        'consultation_fee' => $pricingData['consultation_fee'],
                        'has_night_surcharge' => $pricingData['night_adjustment'] > 0,
                        'has_subscription_discount' => $pricingData['subscription_discount'] > 0,
                    ]
                ];
            } catch (\Exception $e) {
                $result = [
                    'success' => false,
                    'message' => 'Unable to calculate pricing: ' . $e->getMessage()
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * Get task statistics for customer
     */
    public function statistics(): JsonResponse
    {
        $customer = Auth::user();

        $stats = [
            'total_tasks' => Task::where('customer_id', $customer->id)->count(),
            'completed_tasks' => Task::where('customer_id', $customer->id)->completed()->count(),
            'cancelled_tasks' => Task::where('customer_id', $customer->id)->where('status', 'cancelled')->count(),
            'in_progress_tasks' => Task::where('customer_id', $customer->id)->inProgress()->count(),
            'pending_tasks' => Task::where('customer_id', $customer->id)->pending()->count(),
            'total_spent' => Task::where('customer_id', $customer->id)->completed()->sum('final_amount'),
            'average_rating_given' => Task::where('customer_id', $customer->id)->whereNotNull('customer_rating')->avg('customer_rating'),
        ];

        // Category-wise breakdown
        $categoryStats = Task::where('customer_id', $customer->id)
            ->with('category')
            ->selectRaw('category_id, count(*) as count, sum(final_amount) as total_amount')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'count' => $item->count,
                    'total_amount' => $item->total_amount,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'category_breakdown' => $categoryStats,
            ]
        ]);
    }
}