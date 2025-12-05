<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Customer;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CustomerOrderController extends Controller
{
    /**
     * Get customer's orders/tasks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomerOrders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'token' => 'required|string',
                'status' => 'nullable|string|in:all,active,completed,cancelled',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'sort_by' => 'nullable|string|in:created_at,scheduled_at,completed_at',
                'sort_order' => 'nullable|string|in:asc,desc',
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

            // Validate token
            $tokenCheck = AuthHelper::validateToken('customers', $data['phone'], $data['token'], 'phone');
            if (!$tokenCheck['valid']) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => $tokenCheck['status_code'],
                    'status_message' => $tokenCheck['message'],
                    'data' => null
                ], $tokenCheck['status_code']);
            }

            $customer = Customer::where('phone', $data['phone'])->first();
            $customerId = $customer->id;
            $status = $data['status'] ?? 'all';
            $page = $data['page'] ?? 1;
            $perPage = $data['per_page'] ?? 10;
            $sortBy = $data['sort_by'] ?? 'created_at';
            $sortOrder = $data['sort_order'] ?? 'desc';

            // Verify customer exists
            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Build query
            $query = Task::with([
                'category',
                'subcategory',
                'service',
                'customerAddress',
                'serviceProvider.spUser',
                'priceComponents'
            ])
                ->where('customer_id', $customerId)
                ->where('is_active', true);

            // Apply status filter
            switch ($status) {
                case 'active':
                    $query->whereIn('status', [
                        Task::STATUS_REQUESTED,
                        Task::STATUS_SEARCHING,
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
                case 'cancelled':
                    $query->where('status', 'cancelled');
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

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $tasks = $query->paginate($perPage, ['*'], 'page', $page);

            // Format response
            $formattedTasks = $tasks->getCollection()->map(function ($task) {
                return $this->formatOrderResponse($task);
            });

            // Get order statistics
            $statistics = $this->getCustomerOrderStatistics($customerId);

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => [
                    'orders' => $formattedTasks,
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'last_page' => $tasks->lastPage(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ],
                    'statistics' => $statistics,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get specific order details
     *
     * @param Request $request
     * @param int $orderId
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request, int $orderId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->input('customer_id');

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
                'issues'
            ])
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order details retrieved successfully',
                'data' => [
                    'order' => $this->formatDetailedOrderResponse($task),
                    'timeline' => $this->getOrderTimeline($task),
                    'actions' => $this->getCustomerActions($task),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get customer order statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderStatistics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'period' => 'nullable|string|in:week,month,quarter,year',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->input('customer_id');
            $period = $request->input('period', 'month');

            $statistics = $this->getDetailedOrderStatistics($customerId, $period);

            return response()->json([
                'success' => true,
                'message' => 'Order statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get upcoming orders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUpcomingOrders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'days_ahead' => 'nullable|integer|min:1|max:30',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->input('customer_id');
            $daysAhead = $request->input('days_ahead', 7);
            $daysAhead = (int) $daysAhead;
            $upcomingTasks = Task::with([
                'category',
                'subcategory',
                'service',
                'customerAddress',
                'serviceProvider.spUser'
            ])
                ->where('customer_id', $customerId)
                ->where('is_active', true)
                ->whereIn('status', [
                    Task::STATUS_REQUESTED,
                    Task::STATUS_SEARCHING,
                    Task::STATUS_ASSIGNED,
                    Task::STATUS_ON_THE_WAY,
                    Task::STATUS_ARRIVED,
                    Task::STATUS_OTP_START_VERIFIED,
                    Task::STATUS_STARTED,
                    Task::STATUS_PAUSED,
                    Task::STATUS_RESUMED,
                ])


                ->whereBetween('scheduled_at', [now(), now()->addDays($daysAhead)])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            $formattedTasks = $upcomingTasks->map(function ($task) {
                return $this->formatOrderResponse($task);
            });

            return response()->json([
                'success' => true,
                'message' => 'Upcoming orders retrieved successfully',
                'data' => [
                    'upcoming_orders' => $formattedTasks,
                    'total_count' => $upcomingTasks->count(),
                    'period' => $daysAhead . ' days',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming orders',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Format order response for API
     */
    private function formatOrderResponse(Task $task): array
    {
        return [
            'id' => $task->id,
            'order_number' => $task->task_number,
            'status' => $task->status,
            'status_badge' => $task->status_badge,
            'service' => [
                'category' => $task->category->name,
                'subcategory' => $task->subcategory->name,
                'service' => $task->service->name,
                'category_icon' => $task->category->icon,
            ],
            'address' => [
                'address_line_1' => $task->customerAddress->address_line_1,
                'city' => $task->customerAddress->city,
                'pincode' => $task->customerAddress->pincode,
            ],
            'schedule' => [
                'scheduled_at' => $task->scheduled_at->toISOString(),
                'scheduled_date' => $task->scheduled_at->format('Y-m-d'),
                'scheduled_time' => $task->scheduled_at->format('H:i'),
                'start_time' => $task->start_time->format('H:i'),
                'end_time' => $task->end_time->format('H:i'),
                'duration_hours' => $task->requested_hours,
            ],
            'pricing' => [
                'total_amount' => $task->total_amount,
                'gst_amount' => $task->gst_amount,
                'final_amount' => $task->final_amount,
                'currency' => 'INR',
            ],
            'service_provider' => $task->serviceProvider ? [
                'id' => $task->serviceProvider->id,
                'name' => $task->serviceProvider->spUser->name,
                'phone' => $task->serviceProvider->spUser->phone,
                'rating' => $task->serviceProvider->rating,
                'profile_image' => $task->serviceProvider->spUser->profile_image,
            ] : null,
            'ratings' => [
                'customer_rating' => $task->customer_rating,
                'customer_feedback' => $task->customer_feedback,
                'can_rate' => $task->status === Task::STATUS_COMPLETED && !$task->customer_rating,
            ],
            'created_at' => $task->created_at->toISOString(),
            'updated_at' => $task->updated_at->toISOString(),
        ];
    }

    /**
     * Format detailed order response
     */
    private function formatDetailedOrderResponse(Task $task): array
    {
        $response = $this->formatOrderResponse($task);

        // Add detailed information
        $response['details'] = [
            'pax_count' => $task->pax_count,
            'requested_hours' => $task->requested_hours,
            'billable_hours' => $task->billable_hours,
            'dates' => $task->dates,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_display' => $task->recurrence_display,
            'special_instructions' => $task->special_instructions,
        ];

        $response['address_full'] = [
            'id' => $task->customerAddress->id,
            'address_line_1' => $task->customerAddress->address_line_1,
            'address_line_2' => $task->customerAddress->address_line_2,
            'landmark' => $task->customerAddress->landmark,
            'city' => $task->customerAddress->city,
            'state' => $task->customerAddress->state,
            'pincode' => $task->customerAddress->pincode,
            'latitude' => $task->customerAddress->latitude,
            'longitude' => $task->customerAddress->longitude,
        ];

        if ($task->dietaryPreference) {
            $response['dietary_preference'] = [
                'id' => $task->dietaryPreference->id,
                'name' => $task->dietaryPreference->name,
            ];
        }

        if ($task->selectedCuisines->isNotEmpty()) {
            $response['selected_cuisines'] = $task->selectedCuisines->map(function ($cuisine) {
                return [
                    'id' => $cuisine->id,
                    'name' => $cuisine->name,
                ];
            });
        }

        if ($task->addonFlags->isNotEmpty()) {
            $response['addon_flags'] = $task->addonFlags->map(function ($flag) {
                return [
                    'id' => $flag->id,
                    'name' => $flag->name,
                ];
            });
        }

        if ($task->optionalFlags->isNotEmpty()) {
            $response['optional_flags'] = $task->optionalFlags->map(function ($flag) {
                return [
                    'id' => $flag->id,
                    'name' => $flag->name,
                ];
            });
        }

        if ($task->priceComponents) {
            $response['price_breakdown'] = $task->priceComponents->getPriceBreakdown();
        }

        $response['cancellation'] = [
            'cancelled_at' => $task->cancelled_at?->toISOString(),
            'cancellation_reason' => $task->cancellation_reason,
            'cancelled_by' => $task->cancelled_by,
        ];

        $response['otp'] = [
            'start_otp' => $task->start_otp,
            'end_otp' => $task->end_otp,
            'otp_start_verified_at' => $task->otp_start_verified_at?->toISOString(),
            'otp_end_verified_at' => $task->otp_end_verified_at?->toISOString(),
        ];

        return $response;
    }

    /**
     * Get order timeline
     */
    private function getOrderTimeline(Task $task): array
    {
        $timeline = [];

        $timeline[] = [
            'status' => 'requested',
            'label' => 'Order Placed',
            'description' => 'Your booking request has been received',
            'timestamp' => $task->created_at->toISOString(),
            'completed' => true,
        ];

        if ($task->assigned_at) {
            $timeline[] = [
                'status' => 'assigned',
                'label' => 'Service Provider Assigned',
                'description' => 'A service provider has been assigned to your order',
                'timestamp' => $task->assigned_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->started_at) {
            $timeline[] = [
                'status' => 'started',
                'label' => 'Service Started',
                'description' => 'The service has been started',
                'timestamp' => $task->started_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->completed_at) {
            $timeline[] = [
                'status' => 'completed',
                'label' => 'Service Completed',
                'description' => 'The service has been completed successfully',
                'timestamp' => $task->completed_at->toISOString(),
                'completed' => true,
            ];
        }

        if ($task->cancelled_at) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Order Cancelled',
                'description' => 'The order has been cancelled',
                'timestamp' => $task->cancelled_at->toISOString(),
                'completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Get available actions for customer
     */
    private function getCustomerActions(Task $task): array
    {
        $actions = [];

        switch ($task->status) {
            case Task::STATUS_REQUESTED:
            case Task::STATUS_SEARCHING:
                $actions[] = [
                    'action' => 'cancel',
                    'label' => 'Cancel Order',
                    'type' => 'destructive',
                ];
                break;

            case Task::STATUS_ASSIGNED:
            case Task::STATUS_ON_THE_WAY:
                $actions[] = [
                    'action' => 'track',
                    'label' => 'Track Service Provider',
                    'type' => 'primary',
                ];
                $actions[] = [
                    'action' => 'contact_sp',
                    'label' => 'Contact Service Provider',
                    'type' => 'secondary',
                ];
                $actions[] = [
                    'action' => 'cancel',
                    'label' => 'Cancel Order',
                    'type' => 'destructive',
                ];
                break;

            case Task::STATUS_ARRIVED:
                $actions[] = [
                    'action' => 'provide_start_otp',
                    'label' => 'Provide Start OTP',
                    'type' => 'primary',
                ];
                break;

            case Task::STATUS_STARTED:
            case Task::STATUS_PAUSED:
            case Task::STATUS_RESUMED:
                $actions[] = [
                    'action' => 'track_progress',
                    'label' => 'Track Progress',
                    'type' => 'primary',
                ];
                break;

            case Task::STATUS_COMPLETED:
                if (!$task->customer_rating) {
                    $actions[] = [
                        'action' => 'rate_service',
                        'label' => 'Rate Service',
                        'type' => 'primary',
                    ];
                }
                $actions[] = [
                    'action' => 'book_again',
                    'label' => 'Book Again',
                    'type' => 'secondary',
                ];
                break;
        }

        // Common actions
        if (in_array($task->status, [Task::STATUS_ASSIGNED, Task::STATUS_ON_THE_WAY, Task::STATUS_ARRIVED, Task::STATUS_STARTED])) {
            $actions[] = [
                'action' => 'emergency',
                'label' => 'Emergency',
                'type' => 'emergency',
            ];
        }

        return $actions;
    }

    /**
     * Get customer order statistics
     */
    private function getCustomerOrderStatistics(int $customerId): array
    {
        $baseQuery = Task::where('customer_id', $customerId)->where('is_active', true);

        return [
            'total_orders' => $baseQuery->count(),
            'completed_orders' => $baseQuery->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_RATED])->count(),
            'active_orders' => $baseQuery->whereIn('status', [
                Task::STATUS_REQUESTED,
                Task::STATUS_SEARCHING,
                Task::STATUS_ASSIGNED,
                Task::STATUS_ON_THE_WAY,
                Task::STATUS_ARRIVED,
                Task::STATUS_OTP_START_VERIFIED,
                Task::STATUS_STARTED,
                Task::STATUS_PAUSED,
                Task::STATUS_RESUMED,
            ])->count(),
            'cancelled_orders' => $baseQuery->where('status', 'cancelled')->count(),
            'total_spent' => $baseQuery->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_RATED])->sum('final_amount'),
            'average_rating_given' => $baseQuery->whereNotNull('customer_rating')->avg('customer_rating'),
        ];
    }

    /**
     * Get detailed order statistics for a period
     */
    private function getDetailedOrderStatistics(int $customerId, string $period): array
    {
        $startDate = match ($period) {
            'week'    => now()->subWeek(),
            'month'   => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year'    => now()->subYear(),
            default   => now()->subMonth(),
        };

        // Qualify columns with table name
        $baseQuery = Task::query()
            ->where('tasks.customer_id', $customerId)
            ->where('tasks.is_active', true)
            ->where('tasks.created_at', '>=', $startDate);

        $statistics = [
            'period'             => $period,
            'start_date'         => $startDate->toDateString(),
            'end_date'           => now()->toDateString(),

            // each time: CLONE the base query
            'total_orders'       => (clone $baseQuery)->count(),

            'completed_orders'   => (clone $baseQuery)
                ->whereIn('tasks.status', [Task::STATUS_COMPLETED, Task::STATUS_RATED])
                ->count(),

            'cancelled_orders'   => (clone $baseQuery)
                ->where('status', 'cancelled')->count(),

            'total_spent'        => (clone $baseQuery)
                ->whereIn('tasks.status', [Task::STATUS_COMPLETED, Task::STATUS_RATED])
                ->sum('tasks.final_amount'),

            'average_order_value' => 0,
            'favorite_category'   => null,
            'orders_by_category'  => [],
            'orders_by_status'    => [],
        ];

        // Calculate average order value
        if ($statistics['completed_orders'] > 0) {
            $statistics['average_order_value'] =
                $statistics['total_spent'] / $statistics['completed_orders'];
        }

        // Get orders by category
        $ordersByCategory = (clone $baseQuery)
            ->join('categories', 'tasks.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, COUNT(*) as order_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('order_count')
            ->get();

        $statistics['orders_by_category'] = $ordersByCategory->toArray();
        $statistics['favorite_category']  = $ordersByCategory->first()?->category_name;

        // Get orders by status
        $ordersByStatus = (clone $baseQuery)
            ->selectRaw('tasks.status, COUNT(*) as order_count')
            ->groupBy('tasks.status')
            ->get();

        $statistics['orders_by_status'] = $ordersByStatus->toArray();

        return $statistics;
    }
}
