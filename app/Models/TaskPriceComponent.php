<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskPriceComponent extends Model
{
    protected $fillable = [
        'task_id',
        'base_amount',
        'hourly_rate',
        'billable_hours',
        'night_adjustment',
        'festive_surge_percentage',
        'festive_adjustment',
        'weather_surge_percentage',
        'weather_adjustment',
        'premium_sp_percentage',
        'premium_sp_adjustment',
        'consultation_fee',
        'stay_over_fee',
        'subscription_discount',
        'total_excl_gst',
        'gst_percentage',
        'gst_amount',
        'total_incl_gst',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'billable_hours' => 'integer',
        'night_adjustment' => 'decimal:2',
        'festive_surge_percentage' => 'decimal:2',
        'festive_adjustment' => 'decimal:2',
        'weather_surge_percentage' => 'decimal:2',
        'weather_adjustment' => 'decimal:2',
        'premium_sp_percentage' => 'decimal:2',
        'premium_sp_adjustment' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'stay_over_fee' => 'decimal:2',
        'subscription_discount' => 'decimal:2',
        'total_excl_gst' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_incl_gst' => 'decimal:2',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // Helper Methods
    public function getTotalAdjustments(): float
    {
        return $this->night_adjustment 
             + $this->festive_adjustment 
             + $this->weather_adjustment 
             + $this->premium_sp_adjustment 
             + $this->consultation_fee 
             + $this->stay_over_fee;
    }

    public function getTotalDiscounts(): float
    {
        return $this->subscription_discount;
    }

    public function hasNightAdjustment(): bool
    {
        return $this->night_adjustment > 0;
    }

    public function hasFestiveSurge(): bool
    {
        return $this->festive_surge_percentage > 0;
    }

    public function hasWeatherSurge(): bool
    {
        return $this->weather_surge_percentage > 0;
    }

    public function hasPremiumSPCharge(): bool
    {
        return $this->premium_sp_percentage > 0;
    }

    public function hasConsultationFee(): bool
    {
        return $this->consultation_fee > 0;
    }

    public function hasStayOverFee(): bool
    {
        return $this->stay_over_fee > 0;
    }

    public function hasSubscriptionDiscount(): bool
    {
        return $this->subscription_discount > 0;
    }

    public function getPriceBreakdown(): array
    {
        $breakdown = [
            'base_amount' => [
                'label' => 'Base Amount',
                'value' => $this->base_amount,
                'details' => "₹{$this->hourly_rate}/hr × {$this->billable_hours} hrs"
            ]
        ];

        if ($this->hasNightAdjustment()) {
            $breakdown['night_adjustment'] = [
                'label' => 'Night Surcharge',
                'value' => $this->night_adjustment,
                'details' => 'Additional charges for night hours'
            ];
        }

        if ($this->hasFestiveSurge()) {
            $breakdown['festive_adjustment'] = [
                'label' => 'Festive Surge',
                'value' => $this->festive_adjustment,
                'details' => "{$this->festive_surge_percentage}% festive surcharge"
            ];
        }

        if ($this->hasWeatherSurge()) {
            $breakdown['weather_adjustment'] = [
                'label' => 'Weather Surge',
                'value' => $this->weather_adjustment,
                'details' => "{$this->weather_surge_percentage}% weather surcharge"
            ];
        }

        if ($this->hasPremiumSPCharge()) {
            $breakdown['premium_sp_adjustment'] = [
                'label' => 'Premium SP Charge',
                'value' => $this->premium_sp_adjustment,
                'details' => "{$this->premium_sp_percentage}% premium service provider"
            ];
        }

        if ($this->hasConsultationFee()) {
            $breakdown['consultation_fee'] = [
                'label' => 'Consultation Fee',
                'value' => $this->consultation_fee,
                'details' => 'One-time consultation charges'
            ];
        }

        if ($this->hasStayOverFee()) {
            $breakdown['stay_over_fee'] = [
                'label' => 'Stay Over Fee',
                'value' => $this->stay_over_fee,
                'details' => 'Additional stay over charges'
            ];
        }

        if ($this->hasSubscriptionDiscount()) {
            $breakdown['subscription_discount'] = [
                'label' => 'Subscription Discount',
                'value' => -$this->subscription_discount,
                'details' => 'Discount applied from subscription'
            ];
        }

        $breakdown['subtotal'] = [
            'label' => 'Subtotal (Excl. GST)',
            'value' => $this->total_excl_gst,
            'details' => 'Total before GST'
        ];

        $breakdown['gst'] = [
            'label' => "GST ({$this->gst_percentage}%)",
            'value' => $this->gst_amount,
            'details' => 'Goods and Services Tax'
        ];

        $breakdown['total'] = [
            'label' => 'Total Amount',
            'value' => $this->total_incl_gst,
            'details' => 'Final amount including all charges'
        ];

        return $breakdown;
    }

    public function getCustomerDisplayPrice(): array
    {
        // As per requirements, customer sees only simplified price
        return [
            'total_excl_gst' => $this->total_excl_gst,
            'gst_amount' => $this->gst_amount,
            'total_incl_gst' => $this->total_incl_gst,
        ];
    }
}