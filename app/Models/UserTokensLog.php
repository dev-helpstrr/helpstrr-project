<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTokensLog extends Model
{
    //

    // add fillable
    protected $fillable = [];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
