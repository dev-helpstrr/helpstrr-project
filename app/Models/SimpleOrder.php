<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpleOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'service_category_booked',
        'customer_ordered_date_time',
    ];

    protected $guarded = ['id'];
}
