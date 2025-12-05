<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPPerformanceMetric extends Model
{
    protected $table = 'sp_performance_metrics';
    
    protected $fillable = [
        'service_provider_id',
        'metric_date',
        'total_tasks',
        'completed_tasks',
        'cancelled_tasks',
        'tasks_offered',
        'tasks_accepted',
        'tasks_completed',
        'tasks_cancelled',
        'acceptance_rate',
        'completion_rate',
        'average_rating',
        'total_ratings',
        'punctuality_score',
        'quality_score',
        'response_time_avg',
        'complaints_count',
        'complaints_received',
        'total_earnings',
        'online_hours',
        'badge_level',
        'badges',
        'incentives',
        'last_updated'
    ];

    protected $casts = [
        'metric_date' => 'date',
        'acceptance_rate' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'badges' => 'array',
        'incentives' => 'array',
        'last_updated' => 'datetime'
    ];

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function calculateAcceptanceRate(): float
    {
        if ($this->tasks_offered == 0) return 0;
        return round(($this->tasks_accepted / $this->tasks_offered) * 100, 2);
    }

    public function calculateCompletionRate(): float
    {
        if ($this->tasks_accepted == 0) return 0;
        return round(($this->tasks_completed / $this->tasks_accepted) * 100, 2);
    }

    public function getBadgeColor(): string
    {
        return match($this->badge_level) {
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
            default => '#6B7280'
        };
    }
}
