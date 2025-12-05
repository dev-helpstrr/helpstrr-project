<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TaskBroadcast extends Model
{
    protected $fillable = [
        'task_id',
        'service_provider_id',
        'broadcast_round',
        'distance_km',
        'sp_rating',
        'sp_rank_in_round',
        'response',
        'sent_at',
        'responded_at',
        'response_time_seconds',
        'rejection_reason',
        'timeout_seconds',
        'expires_at',
    ];

    protected $casts = [
        'distance_km' => 'integer',
        'sp_rating' => 'decimal:2',
        'sp_rank_in_round' => 'integer',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'response_time_seconds' => 'integer',
        'timeout_seconds' => 'integer',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('response', 'pending');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('response', 'accepted');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('response', 'rejected');
    }

    public function scopeTimeout(Builder $query): Builder
    {
        return $query->where('response', 'timeout');
    }

    public function scopeByRound(Builder $query, string $round): Builder
    {
        return $query->where('broadcast_round', $round);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->response === 'pending' && !$this->isExpired();
    }

    public function getRemainingTimeSeconds(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    public function getResponseTimeDisplay(): ?string
    {
        if (!$this->response_time_seconds) {
            return null;
        }

        if ($this->response_time_seconds < 60) {
            return $this->response_time_seconds . 's';
        }

        $minutes = floor($this->response_time_seconds / 60);
        $seconds = $this->response_time_seconds % 60;
        
        return $minutes . 'm ' . $seconds . 's';
    }

    public function getRoundDisplayName(): string
    {
        return match($this->broadcast_round) {
            'A' => 'Round A (Top 5)',
            'B' => 'Round B (Next 15)',
            'new_sp_boost' => 'New SP Boost',
            'expanded' => 'Expanded Radius',
            default => $this->broadcast_round
        };
    }
}