<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\CustomerAddress;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        // Filter by phone availability
        if ($request->has('has_phone') && $request->has_phone !== '') {
            if ($request->has_phone) {
                $query->whereNotNull('phone');
            } else {
                $query->whereNull('phone');
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $customers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'message' => 'Customers retrieved successfully'
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|unique:customers,phone',
            'password' => 'required|string|min:8',
            'avatar_url' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'avatar_url' => $request->avatar_url,
                'is_active' => $request->get('is_active', true),
            ]);

            // Generate API token
            $token = $customer->createToken('customer-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => $customer,
                'token' => $token,
                'message' => 'Customer created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer retrieved successfully'
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => ['nullable', 'string', Rule::unique('customers')->ignore($customer->id)],
            'password' => 'sometimes|string|min:8',
            'avatar_url' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['name', 'email', 'phone', 'avatar_url', 'is_active']);

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $customer->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $customer->fresh(),
                'message' => 'Customer updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle customer status.
     */
    public function toggleStatus(Customer $customer): JsonResponse
    {
        try {
            $customer->update(['is_active' => !$customer->is_active]);

            return response()->json([
                'success' => true,
                'data' => $customer->fresh(),
                'message' => 'Customer status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::active()->count(),
                'inactive_customers' => Customer::inactive()->count(),
                'customers_with_phone' => Customer::withPhone()->count(),
                'customers_without_phone' => Customer::withoutPhone()->count(),
                'recent_registrations' => Customer::where('created_at', '>=', now()->subDays(7))->count(),
                'customers_with_avatar' => Customer::withAvatar()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Customer statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform bulk actions on customers.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customers = Customer::whereIn('id', $request->customer_ids);
            $count = $customers->count();

            switch ($request->action) {
                case 'activate':
                    $customers->update(['is_active' => true]);
                    $message = "{$count} customers activated successfully";
                    break;
                case 'deactivate':
                    $customers->update(['is_active' => false]);
                    $message = "{$count} customers deactivated successfully";
                    break;
                case 'delete':
                    $customers->delete();
                    $message = "{$count} customers deleted successfully";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
