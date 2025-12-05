<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Issue extends Model
{
    protected $fillable = [
        'issue_number',
        'task_id',
        'customer_id',
        'service_provider_id',
        'reporter_type',
        'reporter_id',
        'reported_by',
        'issue_type',
        'category',
        'title',
        'description',
        'attachments',
        'status',
        'priority',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
        'compensation_amount',
        'compensation_type',
        'compensation_notes',
        'refund_amount',
        'refund_status',
        'is_active',
    ];

    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
        'compensation_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeByReporter(Builder $query, string $reportedBy): Builder
    {
        return $query->where('reported_by', $reportedBy);
    }

    public function scopeByType(Builder $query, string $issueType): Builder
    {
        return $query->where('issue_type', $issueType);
    }

    // Boot method for auto-generating issue number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($issue) {
            if (empty($issue->issue_number)) {
                $issue->issue_number = $issue->generateIssueNumber();
            }
        });
    }

    // Helper Methods
    public function generateIssueNumber(): string
    {
        $prefix = 'ISS';
        $date = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }

    public function getStatusBadgeAttribute(): array
    {
        $statusColors = [
            'open' => ['text' => 'Open', 'color' => 'danger'],
            'in_progress' => ['text' => 'In Progress', 'color' => 'warning'],
            'resolved' => ['text' => 'Resolved', 'color' => 'success'],
            'closed' => ['text' => 'Closed', 'color' => 'gray'],
        ];

        return $statusColors[$this->status] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    public function getPriorityBadgeAttribute(): array
    {
        $priorityColors = [
            'low' => ['text' => 'Low', 'color' => 'info'],
            'medium' => ['text' => 'Medium', 'color' => 'warning'],
            'high' => ['text' => 'High', 'color' => 'danger'],
            'urgent' => ['text' => 'Urgent', 'color' => 'danger'],
        ];

        return $priorityColors[$this->priority] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    public function getIssueTypeDisplayAttribute(): string
    {
        return match($this->issue_type) {
            'quality_issue' => 'Quality Issue',
            'behaviour_issue' => 'Behaviour Issue',
            'timing_issue' => 'Timing Issue',
            'payment_issue' => 'Payment Issue',
            'cancellation_dispute' => 'Cancellation Dispute',
            'rating_dispute' => 'Rating Dispute',
            'other' => 'Other',
            default => 'Unknown'
        };
    }

    public function getReportedByDisplayAttribute(): string
    {
        return match($this->reported_by) {
            'customer' => 'Customer',
            'service_provider' => 'Service Provider',
            'admin' => 'Admin',
            default => 'Unknown'
        };
    }

    public function getCompensationTypeDisplayAttribute(): string
    {
        return match($this->compensation_type) {
            'none' => 'No Compensation',
            'refund' => 'Refund',
            'credit' => 'Credit',
            'discount' => 'Discount',
            default => 'Unknown'
        };
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function hasCompensation(): bool
    {
        return $this->compensation_type !== 'none' && $this->compensation_amount > 0;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function canResolve(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    public function canClose(): bool
    {
        return $this->status === 'resolved';
    }

    public function resolve(string $resolutionNotes, ?float $compensationAmount = null, ?string $compensationType = null): void
    {
        $updates = [
            'status' => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
        ];

        if ($compensationAmount && $compensationType) {
            $updates['compensation_amount'] = $compensationAmount;
            $updates['compensation_type'] = $compensationType;
        }

        $this->update($updates);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_progress',
        ]);
    }

    public function escalate(): void
    {
        $this->update(['priority' => 'urgent']);
    }
}