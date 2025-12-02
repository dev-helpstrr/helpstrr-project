<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_by',
        'referral_to',
        'referral_to_phone',
        'referral_to_name',
        'referral_to_job_role',
        'referred_on',
    ];

    /**
     * Insert new referral record
     */
    public static function createReferral(array $data)
    {
        return self::create($data);
    }

    /**
     * Update referral by referral code
     */
    public static function updateReferralByCode(string $code, array $data)
    {
        return self::where('referral_code', $code)->update($data);
    }

    /**
     * Find referral by referral code
     */
    public static function findByCode(string $code)
    {
        return self::where('referral_code', $code)->first();
    }

    /**
     * Increment click count for referral
     */
    public static function incrementClicks(string $code)
    {
        return self::where('referral_code', $code)->increment('clicks');
    }
}
