<?php

namespace App\Http\Controllers\Api\Mobile\Customer;

use App\Http\Controllers\Controller;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\ServiceProvider;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Get all service categories
     */
    public function getCategories()
    {
        try {
            $categories = NewCategory::where('is_active', true)
                ->with(['subcategories' => function($query) {
                    $query->where('is_active', true)
                          ->select('id', 'category_id', 'name', 'description', 'image', 'base_price', 'duration_minutes');
                }])
                ->select('id', 'name', 'description', 'image', 'icon')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services by category
     */
    public function getServicesByCategory($categoryId)
    {
        try {
            $category = NewCategory::where('id', $categoryId)
                ->where('is_active', true)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $services = Subcategory::where('category_id', $categoryId)
                ->where('is_active', true)
                ->select('id', 'name', 'description', 'image', 'base_price', 'duration_minutes')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'services' => $services
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service details with pricing
     */
    public function getServiceDetails($serviceId)
    {
        try {
            $service = Subcategory::where('id', $serviceId)
                ->where('is_active', true)
                ->with('category')
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            // Get available service providers count
            $availableSPs = ServiceProvider::where('is_active', true)
                ->where('is_verified', true)
                ->whereHas('categories', function($query) use ($service) {
                    $query->where('category_id', $service->category_id);
                })
                ->count();

            // Get pricing details
            $pricing = $this->calculateServicePricing($service);

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'pricing' => $pricing,
                    'available_providers' => $availableSPs,
                    'estimated_duration' => $service->duration_minutes . ' minutes',
                    'features' => [
                        'verified_professionals' => true,
                        'insurance_covered' => true,
                        'money_back_guarantee' => true,
                        'same_day_service' => true
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search services
     */
    public function searchServices(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $categoryId = $request->get('category_id');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');

            $services = Subcategory::where('is_active', true)
                ->when($query, function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->when($categoryId, function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                })
                ->when($minPrice, function($q) use ($minPrice) {
                    $q->where('base_price', '>=', $minPrice);
                })
                ->when($maxPrice, function($q) use ($maxPrice) {
                    $q->where('base_price', '<=', $maxPrice);
                })
                ->with('category:id,name')
                ->select('id', 'category_id', 'name', 'description', 'image', 'base_price', 'duration_minutes')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'services' => $services->items(),
                    'pagination' => [
                        'current_page' => $services->currentPage(),
                        'last_page' => $services->lastPage(),
                        'per_page' => $services->perPage(),
                        'total' => $services->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular services
     */
    public function getPopularServices()
    {
        try {
            // Get services with most bookings in last 30 days
            $popularServices = Subcategory::where('is_active', true)
                ->withCount(['tasks' => function($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                }])
                ->orderBy('tasks_count', 'desc')
                ->limit(10)
                ->with('category:id,name')
                ->select('id', 'category_id', 'name', 'description', 'image', 'base_price', 'duration_minutes')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'popular_services' => $popularServices
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service providers for a service
     */
    public function getServiceProviders(Request $request, $serviceId)
    {
        try {
            $service = Subcategory::find($serviceId);
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');

            $query = ServiceProvider::where('is_active', true)
                ->where('is_verified', true)
                ->whereHas('categories', function($q) use ($service) {
                    $q->where('category_id', $service->category_id);
                })
                ->with(['spUser:id,first_name,last_name,profile_image', 'performanceMetrics']);

            // Add distance calculation if coordinates provided
            if ($latitude && $longitude) {
                $query->selectRaw("
                    *,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                ", [$latitude, $longitude, $latitude])
                ->having('distance', '<=', 50) // Within 50km
                ->orderBy('distance');
            }

            $providers = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'providers' => $providers->items(),
                    'pagination' => [
                        'current_page' => $providers->currentPage(),
                        'last_page' => $providers->lastPage(),
                        'per_page' => $providers->perPage(),
                        'total' => $providers->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service providers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate service pricing
     */
    private function calculateServicePricing($service)
    {
        $basePrice = $service->base_price;
        $platformSettings = PlatformSetting::getSettings();
        
        $gst = $basePrice * ($platformSettings['gst_percentage'] ?? 18) / 100;
        $platformFee = $basePrice * ($platformSettings['platform_fee_percentage'] ?? 5) / 100;
        
        return [
            'base_price' => $basePrice,
            'platform_fee' => $platformFee,
            'gst' => $gst,
            'total_price' => $basePrice + $platformFee + $gst,
            'currency' => 'INR'
        ];
    }
}
