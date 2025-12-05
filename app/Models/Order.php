<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'sp_user_id',
        'order_number',
        'status',
        'amount',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(SPUser::class, 'sp_user_id', 'id');
    }
}
