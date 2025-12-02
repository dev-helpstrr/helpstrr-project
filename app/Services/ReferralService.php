<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\SP\Auth\SortkarSPUser;
use Carbon\Carbon;

class ReferralService
{
    /**
     * Generate referral code like ANUR-PR-REF-ID-001
     */
    public static function generateReferralCode(string $firstName, string $lastName): string
    {
        $f = strtoupper(substr(preg_replace('/\s+/', '', $firstName), 0, 4));
        $l = strtoupper(substr(preg_replace('/\s+/', '', $lastName), 0, 2));
        if ($l === '') $l = strtoupper(substr($f, 0, 2));

        $prefix = $f . '-' . $l . '-REF-ID-';

        $latest = SortkarSPUser::where('referral_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $next = 1;
        if ($latest) {
            $parts = explode('-', $latest->referral_code);
            $next = (int) end($parts) + 1;
        }

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Encode referral ID to make safe for URL
     */
    public static function encodeReferral(string $referralCode): string
    {
        return rtrim(strtr(base64_encode($referralCode), '+/', '-_'), '=');
    }

    /**
     * Decode encoded referral ID
     */
    public static function decodeReferral(string $encoded): string
    {
        $remainder = strlen($encoded) % 4;
        if ($remainder) $encoded .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($encoded, '-_', '+/'));
    }

    /**
     * Create referral for a user
     */
    public static function createReferralForUser($user)
    {


        // ✅ Step 1: Generate referral code
        $referralCode = self::generateReferralCode($user->first_name, $user->last_name);


        $encoded = self::encodeReferral($referralCode);


        // ✅ Step 2: Build referral URL
        $referralUrl = 'referral/registration?ref_id=' . $encoded;

        // ✅ Step 3: Ensure user record exists
        $existingUser = SortkarSPUser::find($user->id);

        if ($existingUser) {

            // Update only if not already set
            if (empty($existingUser->referral_id) || empty($existingUser->referral_url)) {

                $existingUser->update([
                    'referral_id' => $referralCode,
                    'referral_url' => $referralUrl,
                ]);
            }
        }

        return [
            'status' => true,
            'referral_id' => $referralCode,
            'referral_url' => $referralUrl,
        ];
    }


    public static function recordReferralVisit(string $referralCode, int $newUserId)
    {

        $referrer = SortkarSPUser::where('referral_id', $referralCode)->first();

        if ($referrer) {

            Referral::insert([
                'referral_by' => $referrer->id,
                'referral_to' => $newUserId,
                'referred_on' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }


    public static function recordSPInvite($data)
    {
        // Get the referrer user
        $referrer = SortkarSPUser::where('referral_id', $data->referral_id)->first();

        if (! $referrer) {
            return [
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'Referrer not found',
                'data' => null,
            ];
        }

        // 🔍 Check if invite already sent to this phone
        $existingInvite = Referral::where('referral_to_phone', $data->referral_to_phone)->first();

        if ($existingInvite) {
            return [
                'status' => 'failure',
                'status_code' => 409,
                'status_message' => 'Invite already sent to this phone number',
                'data' => null,
            ];
        }

        // ✅ If not exists, insert new referral
        Referral::insert([
            'referral_by' => $referrer->id,
            'referral_to_name' => $data->referral_to_name,
            'referral_to_phone' => $data->referral_to_phone,
            'referral_to_job_role' => $data->referral_to_job_role,
            'referred_on' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'SP Invite Created Successfully',
        ];
    }

    public static function updateReferralTo($phone, $userId)
    {
        $referral = Referral::where('referral_to_phone', $phone)->first();

        if ($referral) {
            $referral->update([
                'referral_to' => $userId,
                'updated_at' => now(),
            ]);

        }
    }
}
