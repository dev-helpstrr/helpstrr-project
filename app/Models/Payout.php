<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Payout extends Model
{
    protected $fillable = [
        'payout_number',
        'service_provider_id',
        'task_id',
        'amount',
        'platform_fee',
        'gross_amount',
        'platform_commission_percentage',
        'platform_commission',
        'tds_percentage',
        'tds_amount',
        'other_deductions',
        'net_amount',
        'status',
        'payout_method',
        'payment_method',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'account_holder_name',
        'upi_id',
        'transaction_id',
        'reference_number',
        'failure_reason',
        'notes',
        'processed_at',
        'completed_at',
        'failed_at',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'platform_commission_percentage' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'tds_percentage' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeByServiceProvider(Builder $query, int $serviceProviderId): Builder
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    // Boot method for auto-generating payout number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            if (empty($payout->payout_number)) {
                $payout->payout_number = $payout->generatePayoutNumber();
            }
        });
    }

    // Helper Methods
    public function generatePayoutNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $random;
    }

    public function getStatusBadgeAttribute(): array
    {
        $statusColors = [
            'pending' => ['text' => 'Pending', 'color' => 'warning'],
            'processing' => ['text' => 'Processing', 'color' => 'info'],
            'completed' => ['text' => 'Completed', 'color' => 'success'],
            'failed' => ['text' => 'Failed', 'color' => 'danger'],
            'cancelled' => ['text' => 'Cancelled', 'color' => 'gray'],
        ];

        return $statusColors[$this->status] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    public function getTotalDeductions(): float
    {
        return $this->platform_commission + $this->tds_amount + $this->other_deductions;
    }

    public function getPayoutMethodDisplayAttribute(): string
    {
        return match($this->payout_method) {
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'wallet' => 'Wallet',
            default => 'Unknown'
        };
    }

    public function canProcess(): bool
    {
        return $this->status === 'pending' && $this->is_active;
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->is_active;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    public function markAsCompleted(string $transactionId, ?string $referenceNumber = null): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'reference_number' => $referenceNumber,
            'completed_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }
}