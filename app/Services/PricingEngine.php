<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Subcategory;
use App\Models\ServiceProvider;
use App\Models\SurgePricing;
use App\Models\CustomerSubscription;
use App\Models\TaskPriceComponent;
use Carbon\Carbon;

class PricingEngine
{
    /**
     * Calculate the complete pricing for a task
     * 
     * This is the OFFICIAL, ONLY VALID pricing model as per requirements:
     * 1. Base Pricing Model - All services use hourly pricing
     * 2. Billable Hours Formula - max(requested_hours, min_hours_for_subcategory)
     * 3. Base Amount - hourly_rate × billable_hours
     * 4. Additional Components - Night, Festive, Weather, Premium SP, Stay-over, Consultation, Subscription
     * 5. Final Price Calculation with all modifiers
     */
    public function calculateTaskPricing(array $taskData, ?ServiceProvider $serviceProvider = null): array
    {
        $subcategory = Subcategory::findOrFail($taskData['subcategory_id']);
        $customerId = $taskData['customer_id'];
        $scheduledAt = Carbon::parse($taskData['scheduled_at']);
        $requestedHours = $taskData['requested_hours'];
        $paxCount = $taskData['pax_count'] ?? 1;
        $optionalFlags = $taskData['optional_flags'] ?? [];

        // Step 1: Calculate billable hours
        $billableHours = max($requestedHours, $subcategory->min_hours);

        // Step 2: Calculate base amount
        $hourlyRate = $subcategory->hourly_rate;
        $baseAmount = $hourlyRate * $billableHours;

        // Step 3: Calculate all modifiers
        $nightAdjustment = $this->calculateNightAdjustment($baseAmount, $subcategory, $scheduledAt);
        $festiveAdjustment = $this->calculateFestiveSurge($baseAmount, $subcategory, $scheduledAt);
        $weatherAdjustment = $this->calculateWeatherSurge($baseAmount, $subcategory, $scheduledAt);
        $premiumSPAdjustment = $this->calculatePremiumSPCharge($baseAmount, $serviceProvider);
        $consultationFee = $subcategory->consultation_fee;
        $stayOverFee = $this->calculateStayOverFee($taskData);
        $subscriptionDiscount = $this->calculateSubscriptionDiscount($customerId, $baseAmount, $subcategory);
        $optionalFlagsAdjustment = $this->calculateOptionalFlagsAdjustment($baseAmount, $optionalFlags);

        // Step 4: Calculate total excluding GST
        $totalExclGST = $baseAmount 
                      + $nightAdjustment['amount']
                      + $festiveAdjustment['amount']
                      + $weatherAdjustment['amount']
                      + $premiumSPAdjustment['amount']
                      + $consultationFee
                      + $stayOverFee
                      + $optionalFlagsAdjustment
                      - $subscriptionDiscount['amount'];

        // Step 5: Calculate GST
        $gstPercentage = 18.00; // Standard GST rate
        $gstAmount = ($totalExclGST * $gstPercentage) / 100;
        $totalInclGST = $totalExclGST + $gstAmount;

        return [
            'base_amount' => $baseAmount,
            'hourly_rate' => $hourlyRate,
            'billable_hours' => $billableHours,
            'night_adjustment' => $nightAdjustment['amount'],
            'festive_surge_percentage' => $festiveAdjustment['percentage'],
            'festive_adjustment' => $festiveAdjustment['amount'],
            'weather_surge_percentage' => $weatherAdjustment['percentage'],
            'weather_adjustment' => $weatherAdjustment['amount'],
            'premium_sp_percentage' => $premiumSPAdjustment['percentage'],
            'premium_sp_adjustment' => $premiumSPAdjustment['amount'],
            'consultation_fee' => $consultationFee,
            'stay_over_fee' => $stayOverFee,
            'subscription_discount' => $subscriptionDiscount['amount'],
            'optional_flags_adjustment' => $optionalFlagsAdjustment,
            'total_excl_gst' => $totalExclGST,
            'gst_percentage' => $gstPercentage,
            'gst_amount' => $gstAmount,
            'total_incl_gst' => $totalInclGST,
        ];
    }

    /**
     * Calculate night adjustment based on category-specific multiplier
     */
    private function calculateNightAdjustment(float $baseAmount, Subcategory $subcategory, Carbon $scheduledAt): array
    {
        $startHour = $scheduledAt->hour;
        $isNightTime = $startHour >= 22 || $startHour <= 6; // 10 PM to 6 AM

        if (!$isNightTime) {
            return ['amount' => 0, 'multiplier' => 1.0];
        }

        $nightMultiplier = $subcategory->category->night_multiplier;
        $nightAdjustment = $baseAmount * ($nightMultiplier - 1);

        return [
            'amount' => round($nightAdjustment, 2),
            'multiplier' => $nightMultiplier
        ];
    }

    /**
     * Calculate festive surge based on active festive pricing
     */
    private function calculateFestiveSurge(float $baseAmount, Subcategory $subcategory, Carbon $scheduledAt): array
    {
        $activeFestiveSurge = SurgePricing::where('type', 'festive')
            ->where('is_active', true)
            ->where('starts_at', '<=', $scheduledAt)
            ->where('ends_at', '>=', $scheduledAt)
            ->whereJsonContains('applicable_categories', $subcategory->category_id)
            ->first();

        if (!$activeFestiveSurge) {
            return ['amount' => 0, 'percentage' => 0];
        }

        $surgePercentage = $activeFestiveSurge->percentage;
        $surgeAmount = ($baseAmount * $surgePercentage) / 100;

        return [
            'amount' => round($surgeAmount, 2),
            'percentage' => $surgePercentage
        ];
    }

    /**
     * Calculate weather surge based on active weather conditions
     */
    private function calculateWeatherSurge(float $baseAmount, Subcategory $subcategory, Carbon $scheduledAt): array
    {
        $activeWeatherSurge = SurgePricing::where('type', 'weather')
            ->where('is_active', true)
            ->where('starts_at', '<=', $scheduledAt)
            ->where('ends_at', '>=', $scheduledAt)
            ->whereJsonContains('applicable_categories', $subcategory->category_id)
            ->first();

        if (!$activeWeatherSurge) {
            return ['amount' => 0, 'percentage' => 0];
        }

        $surgePercentage = $activeWeatherSurge->percentage;
        $surgeAmount = ($baseAmount * $surgePercentage) / 100;

        return [
            'amount' => round($surgeAmount, 2),
            'percentage' => $surgePercentage
        ];
    }

    /**
     * Calculate premium SP charge (Gold = +20% as per requirements)
     */
    private function calculatePremiumSPCharge(float $baseAmount, ?ServiceProvider $serviceProvider): array
    {
        if (!$serviceProvider || !$serviceProvider->is_gold_level) {
            return ['amount' => 0, 'percentage' => 0];
        }

        $premiumPercentage = 20.00; // Gold level = +20% as per requirements
        $premiumAmount = ($baseAmount * $premiumPercentage) / 100;

        return [
            'amount' => round($premiumAmount, 2),
            'percentage' => $premiumPercentage
        ];
    }

    /**
     * Calculate stay-over charges (flat rate)
     */
    private function calculateStayOverFee(array $taskData): float
    {
        // This would be based on specific task requirements
        // For now, returning 0 as it's not specified in the requirements
        return 0;
    }

    /**
     * Calculate subscription discount (flat or percentage)
     */
    private function calculateSubscriptionDiscount(int $customerId, float $baseAmount, Subcategory $subcategory): array
    {
        $activeSubscription = CustomerSubscription::where('customer_id', $customerId)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('subscription')
            ->first();

        if (!$activeSubscription) {
            return ['amount' => 0, 'type' => null];
        }

        $subscription = $activeSubscription->subscription;

        // Check if subscription applies to this category
        if ($subscription->applicable_categories && 
            !in_array($subcategory->category_id, $subscription->applicable_categories)) {
            return ['amount' => 0, 'type' => null];
        }

        $discountAmount = 0;

        if ($subscription->discount_type === 'flat') {
            $discountAmount = $subscription->discount_value;
        } else { // percentage
            $discountAmount = ($baseAmount * $subscription->discount_value) / 100;
            
            // Apply max discount limit if set
            if ($subscription->max_discount_amount && $discountAmount > $subscription->max_discount_amount) {
                $discountAmount = $subscription->max_discount_amount;
            }
        }

        return [
            'amount' => round($discountAmount, 2),
            'type' => $subscription->discount_type
        ];
    }

    /**
     * Calculate optional flags adjustment (e.g., Gold-level chef preference +25%)
     */
    private function calculateOptionalFlagsAdjustment(float $baseAmount, array $optionalFlags): float
    {
        if (empty($optionalFlags)) {
            return 0;
        }

        $totalAdjustment = 0;

        foreach ($optionalFlags as $flagId) {
            $flag = \App\Models\OptionalFlag::find($flagId);
            if ($flag && $flag->price_modifier > 0) {
                $adjustment = ($baseAmount * $flag->price_modifier) / 100;
                $totalAdjustment += $adjustment;
            }
        }

        return round($totalAdjustment, 2);
    }

    /**
     * Store pricing components in database
     */
    public function storePricingComponents(Task $task, array $pricingData): TaskPriceComponent
    {
        return TaskPriceComponent::create([
            'task_id' => $task->id,
            'base_amount' => $pricingData['base_amount'],
            'hourly_rate' => $pricingData['hourly_rate'],
            'billable_hours' => $pricingData['billable_hours'],
            'night_adjustment' => $pricingData['night_adjustment'],
            'festive_surge_percentage' => $pricingData['festive_surge_percentage'],
            'festive_adjustment' => $pricingData['festive_adjustment'],
            'weather_surge_percentage' => $pricingData['weather_surge_percentage'],
            'weather_adjustment' => $pricingData['weather_adjustment'],
            'premium_sp_percentage' => $pricingData['premium_sp_percentage'],
            'premium_sp_adjustment' => $pricingData['premium_sp_adjustment'],
            'consultation_fee' => $pricingData['consultation_fee'],
            'stay_over_fee' => $pricingData['stay_over_fee'],
            'subscription_discount' => $pricingData['subscription_discount'],
            'total_excl_gst' => $pricingData['total_excl_gst'],
            'gst_percentage' => $pricingData['gst_percentage'],
            'gst_amount' => $pricingData['gst_amount'],
            'total_incl_gst' => $pricingData['total_incl_gst'],
        ]);
    }

    /**
     * Validate lead time rule (HARD BLOCK - No booking within 2 hours)
     */
    public function validateLeadTime(Carbon $scheduledAt): bool
    {
        $leadTimeHours = 2; // Hard-coded 2-hour lead time rule
        $minimumScheduleTime = now()->addHours($leadTimeHours);
        
        return $scheduledAt->isAfter($minimumScheduleTime);
    }

    /**
     * Get customer display price (simplified as per requirements)
     */
    public function getCustomerDisplayPrice(array $pricingData): array
    {
        return [
            'total_excl_gst' => $pricingData['total_excl_gst'],
            'gst_amount' => $pricingData['gst_amount'],
            'total_incl_gst' => $pricingData['total_incl_gst'],
        ];
    }
}