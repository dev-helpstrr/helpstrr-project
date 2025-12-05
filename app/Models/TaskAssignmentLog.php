<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TaskAssignmentLog extends Model
{
    protected $fillable = [
        'task_id',
        'service_provider_id',
        'action_type',
        'action_timestamp',
        'response_time_seconds',
        'distance_km',
        'priority_score',
        'conflict_resolution_applied',
        'assignment_round',
        'broadcast_id',
        'rejection_reason',
        'auto_assigned',
        'metadata',
    ];

    protected $casts = [
        'action_timestamp' => 'datetime',
        'conflict_resolution_applied' => 'boolean',
        'auto_assigned' => 'boolean',
        'metadata' => 'array',
    ];

    // Action types
    const ACTION_BROADCAST_SENT = 'broadcast_sent';
    const ACTION_ACCEPTED = 'accepted';
    const ACTION_REJECTED = 'rejected';
    const ACTION_TIMEOUT = 'timeout';
    const ACTION_CONFLICT_RESOLVED = 'conflict_resolved';
    const ACTION_AUTO_ASSIGNED = 'auto_assigned';

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function taskBroadcast(): BelongsTo
    {
        return $this->belongsTo(TaskBroadcast::class, 'broadcast_id');
    }

    /**
     * Get logs for conflict resolution
     */
    public static function getConflictingAcceptances(int $taskId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('task_id', $taskId)
            ->where('action_type', static::ACTION_ACCEPTED)
            ->where('created_at', '>=', now()->subMinutes(2)) // Within 2 minutes
            ->orderBy('action_timestamp')
            ->get();
    }

    /**
     * Calculate priority score for conflict resolution
     */
    public function calculatePriorityScore(): float
    {
        $score = 0;

        // Distance factor (closer = higher score)
        if ($this->distance_km) {
            $score += max(0, 100 - ($this->distance_km * 2));
        }

        // Response time factor (faster = higher score)
        if ($this->response_time_seconds) {
            $score += max(0, 100 - ($this->response_time_seconds / 10));
        }

        // Provider rating factor
        if ($this->serviceProvider) {
            $score += $this->serviceProvider->rating * 10;
        }

        // Provider metrics factor
        if ($this->serviceProvider) {
            $score += $this->serviceProvider->acceptance_rate * 0.5;
            $score += $this->serviceProvider->punctuality_score * 0.3;
        }

        return round($score, 2);
    }
}