<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPKycDocument extends Model
{
    protected $table = 'sp_kyc_documents';
    
    protected $fillable = [
        'service_provider_id',
        'document_type',
        'document_number',
        'document_url',
        'verification_status',
        'rejection_reason',
        'expiry_date',
        'verified_at',
        'verified_by',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expiry_date' => 'date',
        'verified_at' => 'datetime'
    ];

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }
}
