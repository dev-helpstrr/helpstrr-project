<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $table = 'admin_actions_log';
    
    protected $fillable = [
        'admin_user_id',
        'action_type',
        'target_type',
        'target_id',
        'action_description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public static function logAction(
        int $adminUserId,
        string $actionType,
        string $targetType,
        int $targetId,
        string $description,
        array $oldValues = null,
        array $newValues = null
    ): self {
        return self::create([
            'admin_user_id' => $adminUserId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action_description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
