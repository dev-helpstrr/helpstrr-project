<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortkarJobRole extends Model
{
    // add fillable
    protected $fillable = [
        'zoho_job_role_id',
        'role_name',
        'role_status',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];


}
