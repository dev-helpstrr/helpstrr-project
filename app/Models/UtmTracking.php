<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtmTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_referral_id',
        'referrer_url',
        'landing_page_url',
        'ip_address',
        'user_agent',
    ];
}
