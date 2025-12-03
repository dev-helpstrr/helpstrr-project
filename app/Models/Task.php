<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'task_number',
        'customer_id',
        'customer_address_id',
        'category_id',
        'subcategory_id',
        'service_id',
        'service_provider_id',
        'pax_count',
        'requested_hours',
        'billable_hours',
        'dates',
        'start_time',
        'end_time',
        'recurrence_type',
        'dietary_preference_id',
        'status',
        'start_otp',
        'end_otp',
        'otp_start_verified_at',
        'otp_end_verified_at',
        'scheduled_at',
        'assigned_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'total_amount',
        'gst_amount',
        'final_amount',
        'special_instructions',
        'cancellation_reason',
        'cancelled_by',
        'customer_rating',
        'customer_feedback',
        'sp_rating',
        'sp_feedback',
        'is_active',
    ];

    protected $casts = [
        'pax_count' => 'integer',
        'requested_hours' => 'integer',
        'billable_hours' => 'integer',
        'dates' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'otp_start_verified_at' => 'datetime',
        'otp_end_verified_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'customer_rating' => 'integer',
        'sp_rating' => 'integer',
        'is_active' => 'boolean',
    ];

    // Task Status Constants
    const STATUS_REQUESTED = 'requested';
    const STATUS_SEARCHING = 'searching';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_ON_THE_WAY = 'on_the_way';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_OTP_START_VERIFIED = 'otp_start_verified';
    const STATUS_STARTED = 'started';
    const STATUS_PAUSED = 'paused';
    const STATUS_RESUMED = 'resumed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RATED = 'rated';

    // Recurrence Types
    const RECURRENCE_ONE_TIME = 'one_time';
    const RECURRENCE_TWO_DAYS = 'two_days';
    const RECURRENCE_THREE_DAYS = 'three_days';

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(NewSubcategory::class, 'subcategory_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function dietaryPreference(): BelongsTo
    {
        return $this->belongsTo(DietaryPreference::class);
    }

    public function priceComponents(): HasOne
    {
        return $this->hasOne(TaskPriceComponent::class);
    }

    public function selectedCuisines(): BelongsToMany
    {
        return $this->belongsToMany(ChefCuisine::class, 'task_selected_cuisines');
    }

    public function addonFlags(): BelongsToMany
    {
        return $this->belongsToMany(ChefAddonFlag::class, 'task_addon_flags');
    }

    public function optionalFlags(): BelongsToMany
    {
        return $this->belongsToMany(OptionalFlag::class, 'task_optional_flag_link');
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(TaskBroadcast::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByServiceProvider(Builder $query, int $serviceProviderId): Builder
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeScheduledToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeScheduledBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_RATED]);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_ASSIGNED,
            self::STATUS_ON_THE_WAY,
            self::STATUS_ARRIVED,
            self::STATUS_OTP_START_VERIFIED,
            self::STATUS_STARTED,
            self::STATUS_PAUSED,
            self::STATUS_RESUMED,
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_REQUESTED, self::STATUS_SEARCHING]);
    }

    public function scopeRated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RATED);
    }

    public function scopeUnrated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Boot method for auto-generating task number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->task_number)) {
                $task->task_number = $task->generateTaskNumber();
            }
        });
    }

    // Helper Methods
    public function generateTaskNumber(): string
    {
        $prefix = 'TSK';
        $date = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $date . $random;
    }

    public function generateOTP(): string
    {
        return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generateStartOTP(): void
    {
        $this->update(['start_otp' => $this->generateOTP()]);
    }

    public function generateEndOTP(): void
    {
        $this->update(['end_otp' => $this->generateOTP()]);
    }

    public function verifyStartOTP(string $otp): bool
    {
        if ($this->start_otp === $otp) {
            $this->update([
                'otp_start_verified_at' => now(),
                'status' => self::STATUS_OTP_START_VERIFIED,
            ]);
            return true;
        }
        return false;
    }

    public function verifyEndOTP(string $otp): bool
    {
        if ($this->end_otp === $otp) {
            $this->update([
                'otp_end_verified_at' => now(),
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_REQUESTED => [self::STATUS_SEARCHING],
            self::STATUS_SEARCHING => [self::STATUS_ASSIGNED],
            self::STATUS_ASSIGNED => [self::STATUS_ON_THE_WAY],
            self::STATUS_ON_THE_WAY => [self::STATUS_ARRIVED],
            self::STATUS_ARRIVED => [self::STATUS_OTP_START_VERIFIED],
            self::STATUS_OTP_START_VERIFIED => [self::STATUS_STARTED],
            self::STATUS_STARTED => [self::STATUS_PAUSED, self::STATUS_COMPLETED],
            self::STATUS_PAUSED => [self::STATUS_RESUMED],
            self::STATUS_RESUMED => [self::STATUS_PAUSED, self::STATUS_COMPLETED],
            self::STATUS_COMPLETED => [self::STATUS_RATED],
            self::STATUS_RATED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function transitionTo(string $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $updates = ['status' => $newStatus];

        // Add timestamps for specific transitions
        switch ($newStatus) {
            case self::STATUS_ASSIGNED:
                $updates['assigned_at'] = now();
                break;
            case self::STATUS_STARTED:
                $updates['started_at'] = now();
                break;
            case self::STATUS_COMPLETED:
                $updates['completed_at'] = now();
                break;
        }

        $this->update($updates);
        return true;
    }

    public function isChefTask(): bool
    {
        return $this->category->slug === 'chef';
    }

    public function isDriverTask(): bool
    {
        return $this->category->slug === 'driver';
    }

    public function isHouseHelpTask(): bool
    {
        return $this->category->slug === 'house-help';
    }

    public function requiresPaxCount(): bool
    {
        return $this->subcategory->pax_required;
    }

    public function hasRecurrence(): bool
    {
        return $this->recurrence_type !== self::RECURRENCE_ONE_TIME;
    }

    public function isEventCategory(): bool
    {
        return $this->subcategory->is_event_category;
    }

    public function isTakeawayCategory(): bool
    {
        return $this->subcategory->is_takeaway;
    }

    public function hasConsultationFee(): bool
    {
        return $this->subcategory->consultation_fee > 0;
    }

    public function isScheduledWithinLeadTime(): bool
    {
        $leadTimeHours = 2; // Hard-coded 2-hour lead time rule
        return $this->scheduled_at->isAfter(now()->addHours($leadTimeHours));
    }

    public function getDurationInHours(): float
    {
        if (!$this->started_at || !$this->completed_at) {
            return 0;
        }

        return $this->started_at->diffInHours($this->completed_at, true);
    }

    public function getStatusBadgeAttribute(): array
    {
        $statusColors = [
            self::STATUS_REQUESTED => ['text' => 'Requested', 'color' => 'info'],
            self::STATUS_SEARCHING => ['text' => 'Searching', 'color' => 'warning'],
            self::STATUS_ASSIGNED => ['text' => 'Assigned', 'color' => 'primary'],
            self::STATUS_ON_THE_WAY => ['text' => 'On the Way', 'color' => 'primary'],
            self::STATUS_ARRIVED => ['text' => 'Arrived', 'color' => 'primary'],
            self::STATUS_OTP_START_VERIFIED => ['text' => 'OTP Verified', 'color' => 'primary'],
            self::STATUS_STARTED => ['text' => 'In Progress', 'color' => 'success'],
            self::STATUS_PAUSED => ['text' => 'Paused', 'color' => 'warning'],
            self::STATUS_RESUMED => ['text' => 'Resumed', 'color' => 'success'],
            self::STATUS_COMPLETED => ['text' => 'Completed', 'color' => 'success'],
            self::STATUS_RATED => ['text' => 'Rated', 'color' => 'success'],
        ];

        return $statusColors[$this->status] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    public function getRecurrenceDisplayAttribute(): string
    {
        return match($this->recurrence_type) {
            self::RECURRENCE_ONE_TIME => 'One Time',
            self::RECURRENCE_TWO_DAYS => 'Two Days',
            self::RECURRENCE_THREE_DAYS => 'Three Days',
            default => 'Unknown'
        };
    }

    public function getScheduledDatesAttribute(): array
    {
        return array_map(function($date) {
            return Carbon::parse($date)->format('Y-m-d');
        }, $this->dates ?? []);
    }

    public function cancel(string $reason, string $cancelledBy): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }

    public function rateByCustomer(int $rating, string $feedback = null): void
    {
        $this->update([
            'customer_rating' => $rating,
            'customer_feedback' => $feedback,
            'status' => self::STATUS_RATED,
        ]);

        // Update service provider's rating
        if ($this->serviceProvider) {
            $this->serviceProvider->updateRating($rating);
        }
    }

    public function rateBySP(int $rating, string $feedback = null): void
    {
        $this->update([
            'sp_rating' => $rating,
            'sp_feedback' => $feedback,
        ]);
    }
}
