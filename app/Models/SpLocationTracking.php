<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SpLocationTracking extends Model
{
    protected $table = 'sp_location_tracking';

    protected $fillable = [
        'service_provider_id',
        'task_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'status',
        'is_online',
        'device_id',
        'app_version',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'integer',
        'is_online' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_IDLE = 'idle';
    const STATUS_ON_THE_WAY = 'on_the_way';
    const STATUS_AT_LOCATION = 'at_location';
    const STATUS_WORKING = 'working';

    // Relationships
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // Scopes
    public function scopeRecent(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeByProvider(Builder $query, int $serviceProviderId): Builder
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    public function scopeByTask(Builder $query, int $taskId): Builder
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, int $radiusKm): Builder
    {
        return $query->whereRaw(
            "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
            [$latitude, $longitude, $latitude, $radiusKm]
        );
    }

    // Helper Methods
    public function getDistanceFrom(float $latitude, float $longitude): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($latitude - $this->latitude);
        $lonDelta = deg2rad($longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function isStale(int $minutes = 30): bool
    {
        return $this->recorded_at->isBefore(now()->subMinutes($minutes));
    }

    public function getStatusBadgeAttribute(): array
    {
        $statusColors = [
            self::STATUS_IDLE => ['text' => 'Idle', 'color' => 'gray'],
            self::STATUS_ON_THE_WAY => ['text' => 'On the Way', 'color' => 'primary'],
            self::STATUS_AT_LOCATION => ['text' => 'At Location', 'color' => 'success'],
            self::STATUS_WORKING => ['text' => 'Working', 'color' => 'success'],
        ];

        return $statusColors[$this->status] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    public function getFormattedLocationAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    public function getRecordedTimeAgoAttribute(): string
    {
        return $this->recorded_at->diffForHumans();
    }
}