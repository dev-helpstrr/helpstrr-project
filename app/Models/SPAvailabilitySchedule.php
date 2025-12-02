<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPAvailabilitySchedule extends Model
{
    protected $table = 'sp_availability_schedules';

    protected $fillable = [
        'sp_user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'break_start_time',
        'break_end_time',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
    ];

    public function spUser(): BelongsTo
    {
        return $this->belongsTo(SPUser::class, 'sp_user_id');
    }

    // Utility methods
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    public function getWorkingHoursAttribute(): string
    {
        if (!$this->is_available) {
            return 'Not Available';
        }

        if (!$this->start_time || !$this->end_time) {
            return 'Available (Time not specified)';
        }

        $start = $this->start_time->format('H:i');
        $end = $this->end_time->format('H:i');

        if ($this->break_start_time && $this->break_end_time) {
            $breakStart = $this->break_start_time->format('H:i');
            $breakEnd = $this->break_end_time->format('H:i');
            return "{$start} - {$end} (Break: {$breakStart} - {$breakEnd})";
        }

        return "{$start} - {$end}";
    }

    public function getTotalWorkingHoursAttribute(): float
    {
        if (!$this->is_available || !$this->start_time || !$this->end_time) {
            return 0;
        }

        $totalMinutes = $this->end_time->diffInMinutes($this->start_time);

        if ($this->break_start_time && $this->break_end_time) {
            $breakMinutes = $this->break_end_time->diffInMinutes($this->break_start_time);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}