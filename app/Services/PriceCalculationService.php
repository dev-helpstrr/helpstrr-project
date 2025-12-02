<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Task;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Subcategory;
use App\Models\PlatformSetting;

class PriceCalculationService
{
    /**
     * Calculate task pricing based on various factors
     *
     * @param Task $task
     * @return array
     */
    public function calculateTaskPricing(Task $task): array
    {
        // Get base service pricing
        $service = $task->service ?? Service::find($task->service_id);
        $subcategory = $task->subcategory ?? $service->subcategory ?? null;

        // ...

        $priceComponents['consultation_fee'] = $this->calculateConsultationFee($task, $subcategory);


        // Base calculations
        $baseAmount = $service->base_price ?? 0;
        $hourlyRate = $service->hourly_rate ?? 0;
        $billableHours = $task->billable_hours ?? $task->requested_hours;

        // Calculate base amount (base price + hourly charges)
        $totalBaseAmount = $baseAmount + ($hourlyRate * $billableHours);

        // Initialize price components
        $priceComponents = [
            'base_amount' => $totalBaseAmount,
            'hourly_rate' => $hourlyRate,
            'billable_hours' => $billableHours,
            'night_adjustment' => 0,
            'festive_surge_percentage' => 0,
            'festive_adjustment' => 0,
            'weather_surge_percentage' => 0,
            'weather_adjustment' => 0,
            'premium_sp_percentage' => 0,
            'premium_sp_adjustment' => 0,
            'consultation_fee' => 0,
            'stay_over_fee' => 0,
            'subscription_discount' => 0,
        ];

        // Apply night surcharge
        $priceComponents['night_adjustment'] = $this->calculateNightSurcharge($task, $totalBaseAmount);

        // Apply festive surge
        $festiveSurge = $this->calculateFestiveSurge($task, $totalBaseAmount);
        $priceComponents['festive_surge_percentage'] = $festiveSurge['percentage'];
        $priceComponents['festive_adjustment'] = $festiveSurge['amount'];

        // Apply weather surge
        $weatherSurge = $this->calculateWeatherSurge($task, $totalBaseAmount);
        $priceComponents['weather_surge_percentage'] = $weatherSurge['percentage'];
        $priceComponents['weather_adjustment'] = $weatherSurge['amount'];

        // Apply premium SP charges
        $premiumSP = $this->calculatePremiumSPCharges($task, $totalBaseAmount);
        $priceComponents['premium_sp_percentage'] = $premiumSP['percentage'];
        $priceComponents['premium_sp_adjustment'] = $premiumSP['amount'];

        // Apply consultation fee
        $priceComponents['consultation_fee'] = $this->calculateConsultationFee($task, $subcategory);

        // Apply stay over fee (for overnight services)
        $priceComponents['stay_over_fee'] = $this->calculateStayOverFee($task);

        // Apply subscription discount
        $priceComponents['subscription_discount'] = $this->calculateSubscriptionDiscount($task, $totalBaseAmount);

        // Calculate total before GST
        $totalExclGST = $totalBaseAmount
            + $priceComponents['night_adjustment']
            + $priceComponents['festive_adjustment']
            + $priceComponents['weather_adjustment']
            + $priceComponents['premium_sp_adjustment']
            + $priceComponents['consultation_fee']
            + $priceComponents['stay_over_fee']
            - $priceComponents['subscription_discount'];

        // Calculate GST
        $gstPercentage = $this->getGSTPercentage();
        $gstAmount = ($totalExclGST * $gstPercentage) / 100;

        // Calculate final total
        $totalInclGST = $totalExclGST + $gstAmount;

        // Add final calculations to price components
        $priceComponents['total_excl_gst'] = round($totalExclGST, 2);
        $priceComponents['gst_percentage'] = $gstPercentage;
        $priceComponents['gst_amount'] = round($gstAmount, 2);
        $priceComponents['total_incl_gst'] = round($totalInclGST, 2);

        return $priceComponents;
    }

    /**
     * Calculate night surcharge
     *
     * @param Task $task
     * @param float $baseAmount
     * @return float
     */
    private function calculateNightSurcharge(Task $task, float $baseAmount): float
    {
        $scheduledTime = Carbon::parse($task->scheduled_at);
        $startHour = $scheduledTime->hour;

        // Night hours: 10 PM to 6 AM
        if ($startHour >= 22 || $startHour <= 6) {
            $nightSurchargePercentage = $this->getPlatformSetting('night_surcharge_percentage', 25);
            return ($baseAmount * $nightSurchargePercentage) / 100;
        }

        return 0;
    }

    /**
     * Calculate festive surge
     *
     * @param Task $task
     * @param float $baseAmount
     * @return array
     */
    private function calculateFestiveSurge(Task $task, float $baseAmount): array
    {
        $scheduledDate = Carbon::parse($task->scheduled_at)->toDateString();

        // Define festive dates (you can store these in database)
        $festiveDates = [
            '2025-10-24' => 50, // Diwali
            '2025-12-25' => 30, // Christmas
            '2025-01-01' => 40, // New Year
            '2025-03-14' => 25, // Holi
            // Add more festive dates
        ];

        $surchargePercentage = $festiveDates[$scheduledDate] ?? 0;

        if ($surchargePercentage > 0) {
            $surchargeAmount = ($baseAmount * $surchargePercentage) / 100;
            return [
                'percentage' => $surchargePercentage,
                'amount' => $surchargeAmount
            ];
        }

        return ['percentage' => 0, 'amount' => 0];
    }

    /**
     * Calculate weather surge
     *
     * @param Task $task
     * @param float $baseAmount
     * @return array
     */
    private function calculateWeatherSurge(Task $task, float $baseAmount): array
    {
        // This would typically integrate with weather API
        // For now, we'll use a simple logic based on season/month

        $month = Carbon::parse($task->scheduled_at)->month;
        $surchargePercentage = 0;

        // Monsoon months (June to September) - higher surge
        if (in_array($month, [6, 7, 8, 9])) {
            $surchargePercentage = 15;
        }
        // Winter months (December to February) - moderate surge
        elseif (in_array($month, [12, 1, 2])) {
            $surchargePercentage = 10;
        }

        if ($surchargePercentage > 0) {
            $surchargeAmount = ($baseAmount * $surchargePercentage) / 100;
            return [
                'percentage' => $surchargePercentage,
                'amount' => $surchargeAmount
            ];
        }

        return ['percentage' => 0, 'amount' => 0];
    }

    /**
     * Calculate premium service provider charges
     *
     * @param Task $task
     * @param float $baseAmount
     * @return array
     */
    private function calculatePremiumSPCharges(Task $task, float $baseAmount): array
    {
        // If a premium/gold level SP is assigned
        if ($task->serviceProvider && $task->serviceProvider->is_gold_level) {
            $premiumPercentage = $this->getPlatformSetting('premium_sp_percentage', 20);
            $premiumAmount = ($baseAmount * $premiumPercentage) / 100;

            return [
                'percentage' => $premiumPercentage,
                'amount' => $premiumAmount
            ];
        }

        return ['percentage' => 0, 'amount' => 0];
    }

    /**
     * Calculate consultation fee
     *
     * @param Task $task
     * @param Subcategory $subcategory
     * @return float
     */
    private function calculateConsultationFee(Task $task, ?Subcategory $subcategory = null): float
    {
        if (! $subcategory) {
            return 0.0;
        }

        return $subcategory->consultation_fee ?? 0.0;
    }

    /**
     * Calculate stay over fee
     *
     * @param Task $task
     * @return float
     */
    private function calculateStayOverFee(Task $task): float
    {
        // Check if service extends beyond normal hours (e.g., overnight chef service)
        $startTime = Carbon::parse($task->start_time);
        $endTime = Carbon::parse($task->end_time);

        // If service spans midnight or is longer than 12 hours
        if ($endTime->lt($startTime) || $task->requested_hours > 12) {
            return $this->getPlatformSetting('stay_over_fee', 500);
        }

        return 0;
    }

    /**
     * Calculate subscription discount
     *
     * @param Task $task
     * @param float $baseAmount
     * @return float
     */
    private function calculateSubscriptionDiscount(Task $task, float $baseAmount): float
    {
        // Try relation, fall back to manual lookup
        $customer = $task->customer ?? ($task->customer_id ? Customer::find($task->customer_id) : null);

        if (! $customer) {
            // No customer = no discount
            return 0.0;
        }

        // For now, simple logic based on customer history
        $completedTasks = $customer->tasks()
            ->whereIn('status', ['completed', 'rated'])
            ->count();

        if ($completedTasks >= 10) {
            // 10% discount for loyal customers
            return ($baseAmount * 10) / 100;
        } elseif ($completedTasks >= 5) {
            // 5% discount for regular customers
            return ($baseAmount * 5) / 100;
        }

        return 0.0;
    }


    /**
     * Get GST percentage
     *
     * @return float
     */
    private function getGSTPercentage(): float
    {
        return $this->getPlatformSetting('gst_percentage', 18);
    }

    /**
     * Get platform setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getPlatformSetting(string $key, $default = null)
    {
        $setting = PlatformSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get price breakdown for display
     *
     * @param array $priceComponents
     * @return array
     */
    public function getPriceBreakdown(array $priceComponents): array
    {
        $breakdown = [];

        // Base amount
        $breakdown[] = [
            'label' => 'Base Amount',
            'amount' => $priceComponents['base_amount'],
            'type' => 'base',
            'details' => "₹{$priceComponents['hourly_rate']}/hr × {$priceComponents['billable_hours']} hrs"
        ];

        // Night surcharge
        if ($priceComponents['night_adjustment'] > 0) {
            $breakdown[] = [
                'label' => 'Night Surcharge',
                'amount' => $priceComponents['night_adjustment'],
                'type' => 'surcharge',
                'details' => 'Additional charges for night hours'
            ];
        }

        // Festive surge
        if ($priceComponents['festive_adjustment'] > 0) {
            $breakdown[] = [
                'label' => 'Festive Surge',
                'amount' => $priceComponents['festive_adjustment'],
                'type' => 'surge',
                'details' => "{$priceComponents['festive_surge_percentage']}% festive surcharge"
            ];
        }

        // Weather surge
        if ($priceComponents['weather_adjustment'] > 0) {
            $breakdown[] = [
                'label' => 'Weather Surge',
                'amount' => $priceComponents['weather_adjustment'],
                'type' => 'surge',
                'details' => "{$priceComponents['weather_surge_percentage']}% weather surcharge"
            ];
        }

        // Premium SP charge
        if ($priceComponents['premium_sp_adjustment'] > 0) {
            $breakdown[] = [
                'label' => 'Premium Service Provider',
                'amount' => $priceComponents['premium_sp_adjustment'],
                'type' => 'premium',
                'details' => "{$priceComponents['premium_sp_percentage']}% premium service provider"
            ];
        }

        // Consultation fee
        if ($priceComponents['consultation_fee'] > 0) {
            $breakdown[] = [
                'label' => 'Consultation Fee',
                'amount' => $priceComponents['consultation_fee'],
                'type' => 'fee',
                'details' => 'One-time consultation charges'
            ];
        }

        // Stay over fee
        if ($priceComponents['stay_over_fee'] > 0) {
            $breakdown[] = [
                'label' => 'Stay Over Fee',
                'amount' => $priceComponents['stay_over_fee'],
                'type' => 'fee',
                'details' => 'Additional stay over charges'
            ];
        }

        // Subscription discount
        if ($priceComponents['subscription_discount'] > 0) {
            $breakdown[] = [
                'label' => 'Subscription Discount',
                'amount' => -$priceComponents['subscription_discount'],
                'type' => 'discount',
                'details' => 'Discount applied from subscription'
            ];
        }

        // Subtotal
        $breakdown[] = [
            'label' => 'Subtotal (Excl. GST)',
            'amount' => $priceComponents['total_excl_gst'],
            'type' => 'subtotal',
            'details' => 'Total before GST'
        ];

        // GST
        $breakdown[] = [
            'label' => "GST ({$priceComponents['gst_percentage']}%)",
            'amount' => $priceComponents['gst_amount'],
            'type' => 'tax',
            'details' => 'Goods and Services Tax'
        ];

        // Total
        $breakdown[] = [
            'label' => 'Total Amount',
            'amount' => $priceComponents['total_incl_gst'],
            'type' => 'total',
            'details' => 'Final amount including all charges'
        ];

        return $breakdown;
    }

    /**
     * Get surge information
     *
     * @param Task $task
     * @return array
     */
    public function getSurgeInfo(Task $task): array
    {
        $surgeInfo = [
            'is_surge_active' => false,
            'surge_factors' => [],
            'total_surge_percentage' => 0,
        ];

        // Check for night surge
        $scheduledTime = Carbon::parse($task->scheduled_at);
        if ($scheduledTime->hour >= 22 || $scheduledTime->hour <= 6) {
            $surgeInfo['is_surge_active'] = true;
            $surgeInfo['surge_factors'][] = [
                'type' => 'night',
                'label' => 'Night Surcharge',
                'percentage' => 25,
                'reason' => 'Service requested during night hours (10 PM - 6 AM)'
            ];
            $surgeInfo['total_surge_percentage'] += 25;
        }

        // Check for festive surge
        $scheduledDate = $scheduledTime->toDateString();
        $festiveDates = [
            '2025-10-24' => ['name' => 'Diwali', 'percentage' => 50],
            '2025-12-25' => ['name' => 'Christmas', 'percentage' => 30],
            '2025-01-01' => ['name' => 'New Year', 'percentage' => 40],
            '2025-03-14' => ['name' => 'Holi', 'percentage' => 25],
        ];

        if (isset($festiveDates[$scheduledDate])) {
            $festive = $festiveDates[$scheduledDate];
            $surgeInfo['is_surge_active'] = true;
            $surgeInfo['surge_factors'][] = [
                'type' => 'festive',
                'label' => 'Festive Surge',
                'percentage' => $festive['percentage'],
                'reason' => "High demand during {$festive['name']}"
            ];
            $surgeInfo['total_surge_percentage'] += $festive['percentage'];
        }

        // Check for weather surge
        $month = $scheduledTime->month;
        if (in_array($month, [6, 7, 8, 9])) {
            $surgeInfo['is_surge_active'] = true;
            $surgeInfo['surge_factors'][] = [
                'type' => 'weather',
                'label' => 'Monsoon Surge',
                'percentage' => 15,
                'reason' => 'Additional charges during monsoon season'
            ];
            $surgeInfo['total_surge_percentage'] += 15;
        } elseif (in_array($month, [12, 1, 2])) {
            $surgeInfo['is_surge_active'] = true;
            $surgeInfo['surge_factors'][] = [
                'type' => 'weather',
                'label' => 'Winter Surge',
                'percentage' => 10,
                'reason' => 'Additional charges during winter season'
            ];
            $surgeInfo['total_surge_percentage'] += 10;
        }

        return $surgeInfo;
    }

    /**
     * Calculate dynamic pricing based on demand
     *
     * @param Task $task
     * @return array
     */
    public function calculateDynamicPricing(Task $task): array
    {
        // Get current demand in the area
        $demandMultiplier = $this->calculateDemandMultiplier($task);

        // Get supply (available SPs) in the area
        $supplyMultiplier = $this->calculateSupplyMultiplier($task);

        // Calculate dynamic pricing factor
        $dynamicFactor = $demandMultiplier / $supplyMultiplier;

        // Cap the dynamic pricing between 0.8x to 3x
        $dynamicFactor = max(0.8, min(3.0, $dynamicFactor));

        return [
            'dynamic_factor' => $dynamicFactor,
            'demand_multiplier' => $demandMultiplier,
            'supply_multiplier' => $supplyMultiplier,
            'is_surge_pricing' => $dynamicFactor > 1.2,
            'surge_percentage' => ($dynamicFactor - 1) * 100,
        ];
    }

    /**
     * Calculate demand multiplier
     *
     * @param Task $task
     * @return float
     */
    private function calculateDemandMultiplier(Task $task): float
    {
        // Count active tasks in the same area and time slot
        $activeTasksCount = Task::where('customer_address_id', $task->customer_address_id)
            ->whereIn('status', ['requested', 'searching', 'assigned'])
            ->whereBetween('scheduled_at', [
                Carbon::parse($task->scheduled_at)->subHour(),
                Carbon::parse($task->scheduled_at)->addHour()
            ])
            ->count();

        // Base multiplier starts at 1.0, increases with demand
        return 1.0 + ($activeTasksCount * 0.1);
    }

    /**
     * Calculate supply multiplier
     *
     * @param Task $task
     * @return float
     */
    private function calculateSupplyMultiplier(Task $task): float
    {
        // This would calculate available SPs in the area
        // For now, return a default value
        return 1.0;
    }
}
