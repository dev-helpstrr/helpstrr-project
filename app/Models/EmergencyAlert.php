<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyAlert extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'task_id',
        'alert_type',
        'description',
        'latitude',
        'longitude',
        'location_address',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function user()
    {
        if ($this->user_type === 'customer') {
            return $this->belongsTo(Customer::class, 'user_id');
        }
        return $this->belongsTo(ServiceProvider::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function resolve(int $adminUserId, string $notes = null): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $adminUserId,
            'resolution_notes' => $notes
        ]);
    }

    public function markFalseAlarm(int $adminUserId, string $notes = null): bool
    {
        return $this->update([
            'status' => 'false_alarm',
            'resolved_at' => now(),
            'resolved_by' => $adminUserId,
            'resolution_notes' => $notes
        ]);
    }
}
