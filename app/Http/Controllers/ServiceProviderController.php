<?php

namespace App\Http\Controllers;

use App\Models\SPUser;
use App\Models\SPDriverDetail;
use App\Models\SPChefDetail;
use App\Models\SPHouseHelpDetail;
use App\Models\SPAvailabilitySchedule;
use App\Http\Requests\StoreServiceProviderRequest;
use App\Http\Requests\UpdateServiceProviderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of service providers
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = SPUser::with(['driverDetail', 'chefDetail', 'houseHelpDetail', 'availabilitySchedules']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('city')) {
            $query->byLocation($request->city);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'verified':
                    $query->verified();
                    break;
                case 'online':
                    $query->online();
                    break;
            }
        }

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $radius = $request->get('radius', 10);
            $query->withinRadius($request->latitude, $request->longitude, $radius);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'first_name', 'avg_rating', 'experience_years', 'city'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 15);
        $serviceProviders = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $serviceProviders->items(),
                'pagination' => [
                    'current_page' => $serviceProviders->currentPage(),
                    'last_page' => $serviceProviders->lastPage(),
                    'per_page' => $serviceProviders->perPage(),
                    'total' => $serviceProviders->total(),
                ],
                'filters' => $request->only(['search', 'category', 'city', 'status', 'latitude', 'longitude', 'radius']),
            ]);
        }

        return view('service-providers.index', compact('serviceProviders'));
    }

    /**
     * Show the form for creating a new service provider
     */
    public function create(): View
    {
        return view('service-providers.create');
    }

    /**
     * Store a newly created service provider
     */
    public function store(StoreServiceProviderRequest $request): JsonResponse|RedirectResponse
    {

        try {
            DB::beginTransaction();

            // Create main service provider record
            $spData = $request->only([
                'first_name', 'last_name', 'email', 'mobile1_number', 'whatsapp',
                'dob', 'age', 'gender', 'alternate_mobile', 'languages_known',
                'address', 'city', 'state', 'pincode', 'latitude', 'longitude',
                'service_categories', 'experience_years', 'bio', 'expected_hourly_rate',
                'expected_daily_rate', 'max_daily_working_hours', 'max_travel_distance',
                'can_work_weekends', 'can_work_nights', 'preferred_working_areas',
                'special_conditions', 'additional_notes'
            ]);

            $serviceProvider = SPUser::create($spData);

            // Create category-specific details
            $this->createCategoryDetails($serviceProvider, $request);

            // Create availability schedules
            $this->createAvailabilitySchedules($serviceProvider, $request);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service provider created successfully',
                    'data' => $serviceProvider->load(['driverDetail', 'chefDetail', 'houseHelpDetail', 'availabilitySchedules']),
                ], 201);
            }

            return redirect()->route('service-providers.show', $serviceProvider)
                ->with('success', 'Service provider created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create service provider',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to create service provider: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified service provider
     */
    public function show(SPUser $serviceProvider): View|JsonResponse
    {
        $serviceProvider->load(['driverDetail', 'chefDetail', 'houseHelpDetail', 'availabilitySchedules']);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $serviceProvider,
            ]);
        }

        return view('service-providers.show', compact('serviceProvider'));
    }

    /**
     * Show the form for editing the specified service provider
     */
    public function edit(SPUser $serviceProvider): View
    {
        $serviceProvider->load(['driverDetail', 'chefDetail', 'houseHelpDetail', 'availabilitySchedules']);
        return view('service-providers.edit', compact('serviceProvider'));
    }

    /**
     * Update the specified service provider
     */
    public function update(UpdateServiceProviderRequest $request, SPUser $serviceProvider): JsonResponse|RedirectResponse
    {

        try {
            DB::beginTransaction();

            // Update main service provider record
            $spData = $request->only([
                'first_name', 'last_name', 'email', 'mobile1_number', 'whatsapp',
                'dob', 'age', 'gender', 'alternate_mobile', 'languages_known',
                'address', 'city', 'state', 'pincode', 'latitude', 'longitude',
                'service_categories', 'experience_years', 'bio', 'expected_hourly_rate',
                'expected_daily_rate', 'max_daily_working_hours', 'max_travel_distance',
                'can_work_weekends', 'can_work_nights', 'preferred_working_areas',
                'special_conditions', 'additional_notes', 'is_active', 'is_verified',
                'is_online'
            ]);

            $serviceProvider->update($spData);

            // Update category-specific details
            $this->updateCategoryDetails($serviceProvider, $request);

            // Update availability schedules
            $this->updateAvailabilitySchedules($serviceProvider, $request);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service provider updated successfully',
                    'data' => $serviceProvider->fresh()->load(['driverDetail', 'chefDetail', 'houseHelpDetail', 'availabilitySchedules']),
                ]);
            }

            return redirect()->route('service-providers.show', $serviceProvider)
                ->with('success', 'Service provider updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update service provider',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to update service provider: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified service provider
     */
    public function destroy(SPUser $serviceProvider): JsonResponse|RedirectResponse
    {
        try {
            $serviceProvider->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service provider deleted successfully',
                ]);
            }

            return redirect()->route('service-providers.index')
                ->with('success', 'Service provider deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete service provider',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete service provider: ' . $e->getMessage());
        }
    }

    /**
     * Toggle service provider status
     */
    public function toggleStatus(SPUser $serviceProvider): JsonResponse|RedirectResponse
    {
        try {
            $serviceProvider->update(['is_active' => !$serviceProvider->is_active]);

            $status = $serviceProvider->is_active ? 'activated' : 'deactivated';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Service provider {$status} successfully",
                    'data' => ['is_active' => $serviceProvider->is_active],
                ]);
            }

            return back()->with('success', "Service provider {$status} successfully");

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle status',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Get service provider statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => SPUser::count(),
            'active' => SPUser::active()->count(),
            'verified' => SPUser::verified()->count(),
            'online' => SPUser::online()->count(),
            'by_category' => [
                'drivers' => SPUser::byCategory('driver')->count(),
                'chefs' => SPUser::byCategory('chef')->count(),
                'house_help' => SPUser::byCategory('house_help')->count(),
            ],
            'recent_registrations' => SPUser::where('created_at', '>=', now()->subDays(7))->count(),
            'avg_rating' => SPUser::whereNotNull('avg_rating')->avg('avg_rating'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Bulk actions on service providers
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,verify,reject,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:s_p_users,id',
        ]);

        try {
            $count = 0;
            $serviceProviders = SPUser::whereIn('id', $request->ids);

            switch ($request->action) {
                case 'activate':
                    $count = $serviceProviders->update(['is_active' => true]);
                    break;
                case 'deactivate':
                    $count = $serviceProviders->update(['is_active' => false]);
                    break;
                case 'verify':
                    $count = $serviceProviders->update(['is_verified' => 1]);
                    break;
                case 'reject':
                    $count = $serviceProviders->update(['is_verified' => 2]);
                    break;
                case 'delete':
                    $count = $serviceProviders->delete();
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} service providers {$request->action}d successfully",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Create category-specific details
     */
    private function createCategoryDetails(SPUser $serviceProvider, Request $request): void
    {
        $categories = $request->get('service_categories', []);

        if (in_array('driver', $categories) && $request->has('driver_detail')) {
            $serviceProvider->driverDetail()->create($request->get('driver_detail', []));
        }

        if (in_array('chef', $categories) && $request->has('chef_detail')) {
            $serviceProvider->chefDetail()->create($request->get('chef_detail', []));
        }

        if (in_array('house_help', $categories) && $request->has('house_help_detail')) {
            $serviceProvider->houseHelpDetail()->create($request->get('house_help_detail', []));
        }
    }

    /**
     * Update category-specific details
     */
    private function updateCategoryDetails(SPUser $serviceProvider, Request $request): void
    {
        $categories = $request->get('service_categories', []);

        // Driver details
        if (in_array('driver', $categories)) {
            if ($request->has('driver_detail')) {
                $serviceProvider->driverDetail()->updateOrCreate(
                    ['sp_user_id' => $serviceProvider->id],
                    $request->get('driver_detail', [])
                );
            }
        } else {
            $serviceProvider->driverDetail()->delete();
        }

        // Chef details
        if (in_array('chef', $categories)) {
            if ($request->has('chef_detail')) {
                $serviceProvider->chefDetail()->updateOrCreate(
                    ['sp_user_id' => $serviceProvider->id],
                    $request->get('chef_detail', [])
                );
            }
        } else {
            $serviceProvider->chefDetail()->delete();
        }

        // House help details
        if (in_array('house_help', $categories)) {
            if ($request->has('house_help_detail')) {
                $serviceProvider->houseHelpDetail()->updateOrCreate(
                    ['sp_user_id' => $serviceProvider->id],
                    $request->get('house_help_detail', [])
                );
            }
        } else {
            $serviceProvider->houseHelpDetail()->delete();
        }
    }

    /**
     * Create availability schedules
     */
    private function createAvailabilitySchedules(SPUser $serviceProvider, Request $request): void
    {
        if ($request->has('availability_schedules')) {
            foreach ($request->get('availability_schedules', []) as $schedule) {
                $serviceProvider->availabilitySchedules()->create($schedule);
            }
        }
    }

    /**
     * Update availability schedules
     */
    private function updateAvailabilitySchedules(SPUser $serviceProvider, Request $request): void
    {
        if ($request->has('availability_schedules')) {
            // Delete existing schedules
            $serviceProvider->availabilitySchedules()->delete();
            
            // Create new schedules
            foreach ($request->get('availability_schedules', []) as $schedule) {
                $serviceProvider->availabilitySchedules()->create($schedule);
            }
        }
    }
}