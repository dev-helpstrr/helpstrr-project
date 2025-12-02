<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPanel extends Model
{
    //

    protected $table = 'scheduled_notifications'; // ✅ your actual table name
    // add fillable
    protected $fillable = [];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'user_ids' => 'array',
    ];
}
