<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class PricingController extends Controller
{
    /**
     * Calculate pricing for a service request
     */
    public function calculatePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'service_duration' => 'required|integer|min:1', // in hours
            'customer_latitude' => 'required|numeric',
            'customer_longitude' => 'required|numeric',
            'scheduled_at' => 'required|date|after:now',
            'additional_services' => 'sometimes|array',
            'additional_services.*' => 'integer|exists:subcategories,id',
            'urgency_level' => 'sometimes|in:normal,urgent,emergency'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subcategory = Subcategory::with('category')->find($request->subcategory_id);
            
            if (!$subcategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            // Calculate base pricing
            $pricing = $this->calculateBasePricing($subcategory, $request->service_duration);
            
            // Add travel charges
            $travelCharges = $this->calculateTravelCharges($request->customer_latitude, $request->customer_longitude);
            $pricing['travel_charges'] = $travelCharges;
            
            // Apply surge pricing if applicable
            $surgeMultiplier = $this->calculateSurgeMultiplier($request->scheduled_at, $request->urgency_level);
            $pricing['surge_multiplier'] = $surgeMultiplier;
            
            // Calculate additional services
            $additionalServicesCost = 0;
            if (!empty($request->additional_services)) {
                $additionalServicesCost = $this->calculateAdditionalServices($request->additional_services);
            }
            $pricing['additional_services_cost'] = $additionalServicesCost;
            
            // Calculate final amounts
            $subtotal = ($pricing['base_amount'] + $additionalServicesCost) * $surgeMultiplier + $travelCharges;
            $platformFee = $this->calculatePlatformFee($subtotal);
            $taxes = $this->calculateTaxes($subtotal);
            $totalAmount = $subtotal + $platformFee + $taxes;
            
            // Calculate SP amount (after platform commission)
            $platformCommission = $this->calculatePlatformCommission($subtotal);
            $spAmount = $subtotal - $platformCommission;
            
            $finalPricing = [
                'base_amount' => $pricing['base_amount'],
                'travel_charges' => $travelCharges,
                'additional_services_cost' => $additionalServicesCost,
                'surge_multiplier' => $surgeMultiplier,
                'surge_amount' => ($pricing['base_amount'] + $additionalServicesCost) * ($surgeMultiplier - 1),
                'subtotal' => $subtotal,
                'platform_fee' => $platformFee,
                'taxes' => $taxes,
                'total_amount' => $totalAmount,
                'sp_amount' => $spAmount,
                'platform_commission' => $platformCommission,
                'currency' => 'INR',
                'breakdown' => [
                    'base_rate_per_hour' => $subcategory->base_price ?? 0,
                    'service_duration_hours' => $request->service_duration,
                    'travel_distance_km' => $pricing['travel_distance'] ?? 0,
                    'travel_rate_per_km' => PlatformSetting::getValue('travel_rate_per_km', 10),
                    'platform_fee_percentage' => PlatformSetting::getValue('platform_fee_percentage', 5),
                    'platform_commission_percentage' => PlatformSetting::getValue('platform_commission_percentage', 15),
                    'tax_percentage' => PlatformSetting::getValue('tax_percentage', 18)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'pricing' => $finalPricing,
                    'service' => [
                        'category' => $subcategory->category->name,
                        'subcategory' => $subcategory->name,
                        'duration' => $request->service_duration . ' hours'
                    ],
                    'valid_until' => now()->addMinutes(30)->toISOString() // Price valid for 30 minutes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get surge pricing information
     */
    public function getSurgePricing(Request $request)
    {
        try {
            $currentTime = now();
            $surgeAreas = $this->getSurgeAreas();
            $surgeFactors = $this->getCurrentSurgeFactors();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_surge_multiplier' => $surgeFactors['current'],
                    'peak_hours' => [
                        'morning' => '08:00 - 10:00',
                        'evening' => '18:00 - 21:00',
                        'weekend' => 'Saturday - Sunday'
                    ],
                    'surge_areas' => $surgeAreas,
                    'surge_factors' => $surgeFactors,
                    'next_update' => now()->addHour()->startOfHour()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch surge pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cancellation policy and charges
     */
    public function getCancellationPolicy(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        try {
            $policy = [
                'free_cancellation_window' => 60, // minutes before scheduled time
                'cancellation_charges' => [
                    'within_1_hour' => [
                        'percentage' => 50,
                        'description' => '50% of total amount if cancelled within 1 hour of scheduled time'
                    ],
                    'within_30_minutes' => [
                        'percentage' => 75,
                        'description' => '75% of total amount if cancelled within 30 minutes of scheduled time'
                    ],
                    'after_sp_assigned' => [
                        'percentage' => 25,
                        'description' => '25% of total amount if cancelled after service provider is assigned'
                    ],
                    'after_sp_started_journey' => [
                        'percentage' => 100,
                        'description' => 'Full amount if cancelled after service provider has started journey'
                    ]
                ],
                'refund_policy' => [
                    'processing_time' => '3-5 business days',
                    'refund_method' => 'Original payment method',
                    'partial_refunds' => 'Available based on service completion percentage'
                ],
                'emergency_cancellation' => [
                    'allowed' => true,
                    'charges' => 0,
                    'conditions' => 'Medical emergency, natural disaster, or safety concerns'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'cancellation_policy' => $policy,
                    'category_specific_rules' => $this->getCategorySpecificCancellationRules($categoryId)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cancellation policy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate cancellation charges for a specific booking
     */
    public function calculateCancellationCharges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'cancellation_reason' => 'required|in:customer_request,emergency,service_provider_unavailable,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = \App\Models\Task::find($request->task_id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $cancellationCharges = $this->calculateCancellationAmount($task, $request->cancellation_reason);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'task_id' => $task->id,
                    'original_amount' => $task->total_amount,
                    'cancellation_charges' => $cancellationCharges['charges'],
                    'refund_amount' => $cancellationCharges['refund'],
                    'cancellation_reason' => $request->cancellation_reason,
                    'policy_applied' => $cancellationCharges['policy'],
                    'refund_timeline' => '3-5 business days'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate cancellation charges',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate base pricing for a service
     */
    private function calculateBasePricing($subcategory, $duration)
    {
        $basePrice = $subcategory->base_price ?? 0;
        $baseAmount = $basePrice * $duration;
        
        return [
            'base_amount' => $baseAmount,
            'hourly_rate' => $basePrice
        ];
    }

    /**
     * Calculate travel charges based on distance
     */
    private function calculateTravelCharges($latitude, $longitude)
    {
        // For now, use a fixed travel charge
        // In production, calculate actual distance from nearest SP
        $averageDistance = 5; // km
        $travelRatePerKm = PlatformSetting::getValue('travel_rate_per_km', 10);
        
        return $averageDistance * $travelRatePerKm;
    }

    /**
     * Calculate surge multiplier based on demand and time
     */
    private function calculateSurgeMultiplier($scheduledAt, $urgencyLevel = 'normal')
    {
        $baseMultiplier = 1.0;
        $scheduledTime = \Carbon\Carbon::parse($scheduledAt);
        
        // Peak hours surge
        $hour = $scheduledTime->hour;
        if (($hour >= 8 && $hour <= 10) || ($hour >= 18 && $hour <= 21)) {
            $baseMultiplier += 0.2; // 20% surge during peak hours
        }
        
        // Weekend surge
        if ($scheduledTime->isWeekend()) {
            $baseMultiplier += 0.15; // 15% weekend surge
        }
        
        // Urgency surge
        switch ($urgencyLevel) {
            case 'urgent':
                $baseMultiplier += 0.25; // 25% for urgent requests
                break;
            case 'emergency':
                $baseMultiplier += 0.5; // 50% for emergency requests
                break;
        }
        
        // Dynamic demand surge (cached for performance)
        $demandSurge = Cache::get('current_demand_surge', 0);
        $baseMultiplier += $demandSurge;
        
        return round($baseMultiplier, 2);
    }

    /**
     * Calculate additional services cost
     */
    private function calculateAdditionalServices($additionalServiceIds)
    {
        $additionalServices = Subcategory::whereIn('id', $additionalServiceIds)->get();
        $totalCost = 0;
        
        foreach ($additionalServices as $service) {
            $totalCost += $service->base_price ?? 0;
        }
        
        return $totalCost;
    }

    /**
     * Calculate platform fee
     */
    private function calculatePlatformFee($subtotal)
    {
        $feePercentage = PlatformSetting::getValue('platform_fee_percentage', 5);
        return round(($subtotal * $feePercentage) / 100, 2);
    }

    /**
     * Calculate taxes
     */
    private function calculateTaxes($subtotal)
    {
        $taxPercentage = PlatformSetting::getValue('tax_percentage', 18);
        return round(($subtotal * $taxPercentage) / 100, 2);
    }

    /**
     * Calculate platform commission
     */
    private function calculatePlatformCommission($subtotal)
    {
        $commissionPercentage = PlatformSetting::getValue('platform_commission_percentage', 15);
        return round(($subtotal * $commissionPercentage) / 100, 2);
    }

    /**
     * Get current surge areas
     */
    private function getSurgeAreas()
    {
        return Cache::get('surge_areas', [
            'high_demand' => ['Sector V', 'Park Street', 'Ballygunge'],
            'medium_demand' => ['Howrah', 'Jadavpur', 'Garia'],
            'normal_demand' => ['Other areas']
        ]);
    }

    /**
     * Get current surge factors
     */
    private function getCurrentSurgeFactors()
    {
        return [
            'current' => Cache::get('current_surge_multiplier', 1.0),
            'peak_hours' => 1.2,
            'weekend' => 1.15,
            'urgent' => 1.25,
            'emergency' => 1.5,
            'high_demand_areas' => 1.3
        ];
    }

    /**
     * Get category-specific cancellation rules
     */
    private function getCategorySpecificCancellationRules($categoryId)
    {
        // Different categories may have different cancellation policies
        $rules = [
            'house_help' => [
                'advance_notice_required' => '2 hours',
                'recurring_booking_penalty' => '10% additional charge'
            ],
            'driver' => [
                'advance_notice_required' => '30 minutes',
                'airport_pickup_penalty' => '50% additional charge'
            ],
            'chef' => [
                'advance_notice_required' => '4 hours',
                'ingredient_procurement_penalty' => '25% additional charge'
            ]
        ];
        
        return $rules['default'] ?? [
            'advance_notice_required' => '1 hour',
            'additional_charges' => 'As per standard policy'
        ];
    }

    /**
     * Calculate actual cancellation amount
     */
    private function calculateCancellationAmount($task, $reason)
    {
        $totalAmount = $task->total_amount;
        $scheduledAt = $task->scheduled_at;
        $currentTime = now();
        $timeUntilService = $currentTime->diffInMinutes($scheduledAt);
        
        // Emergency cancellations are free
        if ($reason === 'emergency') {
            return [
                'charges' => 0,
                'refund' => $totalAmount,
                'policy' => 'Emergency cancellation - no charges'
            ];
        }
        
        // Calculate charges based on timing
        if ($timeUntilService > 60) {
            // Free cancellation
            return [
                'charges' => 0,
                'refund' => $totalAmount,
                'policy' => 'Free cancellation (more than 1 hour notice)'
            ];
        } elseif ($timeUntilService > 30) {
            // 50% charges
            $charges = $totalAmount * 0.5;
            return [
                'charges' => $charges,
                'refund' => $totalAmount - $charges,
                'policy' => '50% cancellation charges (within 1 hour)'
            ];
        } else {
            // 75% charges
            $charges = $totalAmount * 0.75;
            return [
                'charges' => $charges,
                'refund' => $totalAmount - $charges,
                'policy' => '75% cancellation charges (within 30 minutes)'
            ];
        }
    }
}
