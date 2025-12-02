<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserTokensLog;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'fcm_token',
        'device_id',
        'user_id',
        'user_type',
        'installed_on',
    ];

    public static function handleSubmission($data)
    {
        // 🔍 Validate input
        $validator = Validator::make($data, [
            'fcm_token' => 'required|string|max:350',
            'device_id' => 'nullable|string|max:200',
            'user_id'   => 'nullable|integer',
            'user_type' => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return [
                'status'  => false,
                'message' => $validator->errors()->first(),
            ];
        }

        // 🧩 Check for existing token
        $existingToken = self::where('fcm_token', $data['fcm_token'])->first();

        if ($existingToken) {
            // Update existing token
            $existingToken->update([
                'device_id'   => $data['device_id'] ?? null,
                'user_id'     => $data['user_id'] ?? null,
                'user_type'   => $data['user_type'] ?? null,
                'installed_on' => Carbon::now(),
            ]);

            $action = 'updated';
            $token = $existingToken;
        } else {
            // Create new record
            $token = self::create([
                'fcm_token'   => $data['fcm_token'],
                'device_id'   => $data['device_id'] ?? null,
                'user_id'     => $data['user_id'] ?? null,
                'user_type'   => $data['user_type'] ?? null,
                'installed_on' => Carbon::now(),
            ]);

            $action = 'created';
        }

        // 🧾 Always log it
        UserTokensLog::create([
            'fcm_token'   => $data['fcm_token'],
            'device_id'   => $data['device_id'] ?? null,
            'user_id'     => $data['user_id'] ?? null,
            'user_type'   => $data['user_type'] ?? null,
            'installed_on' => Carbon::now(),
        ]);

        return [
            'status'  => true,
            'message' => "Token successfully {$action}.",
            'data'    => $token
        ];
    }
}
