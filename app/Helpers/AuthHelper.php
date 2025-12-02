<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthHelper
{
    /**
     * Validate phone number format
     */
    public static function validatePhone($value, $phoneColumnName)
    {
        $validator = Validator::make(
            [$phoneColumnName => $value],
            [
                $phoneColumnName => [
                    'required',
                    'regex:/^[1-9][0-9]{9}$/'
                ]
            ],
            [
                $phoneColumnName . '.required' => ucfirst(str_replace('_', ' ', $phoneColumnName)) . ' field is required.',
                $phoneColumnName . '.regex' => 'Invalid ' . str_replace('_', ' ', $phoneColumnName) . '. Please enter a valid 10-digit number that does not start with 0.'
            ]
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'message' => $validator->errors()->first(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate user + token combination dynamically
     */
    public static function validateToken($table, $phone, $token, $phoneColumn)
    {
        $user = DB::table($table)->where($phoneColumn, $phone)->first();
// testing
        if (!$user) {
            return [
                'valid' => false,
                'status_code' => 404,
                'message' => 'Phone number not found',
                'user' => null
            ];
        }

        if ($user->token !== $token) {
            return [
                'valid' => false,
                'status_code' => 401,
                'message' => 'Invalid token',

            ];
        }

        return [
            'valid' => true,
            'user' => $user
        ];
    }

    public static function generateOtp()
    {
        $length = config('otp.length', 4);
        $type   = config('otp.type', 'numeric');

        if ($type === 'alphanumeric') {
            $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $otp = substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
        } else {
            $otp = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        }

        return $otp;
    }
}
