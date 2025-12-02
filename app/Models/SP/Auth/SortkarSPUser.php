<?php

namespace App\Models\SP\Auth;

use Log;
use Carbon\Carbon;
use App\Helpers\AWSHelper;
use App\Helpers\AuthHelper;
use App\Helpers\ZohoHelper;
use App\Services\ReferralService;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SortkarSPUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 's_p_users';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile1_number',
        'whatsapp',
        'dob',
        'gender',
        'profile_status',
        'avatar_url',
        'intrested_role',
        'prior_experience',
        'password',
        'confirm_password_hash',
        'address',
        'city',
        'country',
        'state',
        'pincode',
        'coverage_radius',
        'latitude',
        'longitude',
        'experience_years',
        'bio',
        'profile_picture',
        'id_proof',
        'background_check_status',
        'avg_rating',
        'total_ratings',
        'is_verified',
        'token',
        'is_active',
        'token',
        'referral_id',
        'referral_url',
        'login_otp',
        'is_opt_in',
        'last_login',
        'latitude',
        'longitude',
        'has_two_wheeler',
        'zoho_pushed_status'
    ];

    /**
     * Handle SP Signup Logic
     */
    public static function handleSignup($data)
    {
        // ✅ Use centralized phone validation
        $phoneCheck = AuthHelper::validatePhone($data['phone'], 'mobile1_number');
        if (!$phoneCheck['valid']) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => $phoneCheck['message']
            ];
        }

        $otp = AuthHelper::generateOtp();
        $nh = new NotificationHelper();

        try {
            $user = self::where('mobile1_number', $data['phone'])->first();

            if ($user) {
                $user->update(['login_otp' => $otp]);
                $nh->sendNotification('whatsapp', [
                    'phone' => $data['phone'],
                    'otp'   => $otp,
                ]);


                return [
                    'status' => 'success',
                    'status_code' => 200,
                    'status_message' => 'Phone number is already registered as a Service Provider.',
                    'otp'   => $otp,
                ];
            }

            // Create new SP user
            $user = self::create([
                'mobile1_number' => $data['phone'],
                'login_otp' => $otp,
            ]);

            $nh->sendNotification('whatsapp', [
                'phone' => $data['phone'],
                'otp'   => $otp,
            ]);


            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'New Service Provider. Proceed with sign-up.'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function verifyOtpAndCreateToken($data)
    {

        // ✅ Centralized phone validation
        $phoneCheck = AuthHelper::validatePhone($data['phone'], 'mobile1_number');
        if (!$phoneCheck['valid']) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => $phoneCheck['message']
            ];
        }

        $otp = $data['otp'];
        $phone = $data['phone'];

        // ✅ Special bypass: if number is 9576329358 and OTP is 1111
        if ($phone == '9576329358' && $otp == '1111') {
            $user = self::where('mobile1_number', $phone)->first();

            if (!$user) {
                return [
                    'status' => 'failure',
                    'status_code' => 404,
                    'status_message' => 'User not found for test number'
                ];
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->update([
                'login_otp' => null,
                'is_opt_in' => 1,
                'token' => $token
            ]);

            // ✅ update referral
            ReferralService::updateReferralTo($phone, $user->id);

            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'OTP Verified (Bypassed)',
                'user' => $user,
                'token' => $token
            ];
        }

        // ✅ Normal OTP verification
        $user = self::where('mobile1_number', $phone)->first();

        if (!$user || $user->login_otp !== $otp) {
            return [
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Otp Verification Failed'
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->update([
            'login_otp' => null,
            'is_opt_in' => 1,
            'token' => $token
        ]);

        // ✅ update referral
        ReferralService::updateReferralTo($phone, $user->id);

        return [
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'OTP Verified Successfully',
            'user' => $user,
            'token' => $token
        ];
    }



    public static function verifyAndGetDetails($phone, $token)
    {
        $user = self::where('mobile1_number', $phone)->first();

        if (! $user) {
            return [
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'Phone number not found',
                'data' => null
            ];
        }

        if ($user->token !== $token) {
            return [
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Invalid token',
                'data' => null
            ];
        }

        // Mandatory fields
        $mandatoryFields = [
            'first_name',
            'last_name',
            'mobile1_number',
            'whatsapp',
            'email',
            'dob',
            'intrested_role',
            'pincode',
            'state',
            'city',
            'country',
            'address',

        ];

        $allFilled = true;

        foreach ($mandatoryFields as $field) {
            if (empty($user->$field)) {   // ✅ checks for null, '', 0, []
                $allFilled = false;
                break;
            }
        }

        if (! $allFilled) {
            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Profile details need to be filled',
                'data' => $user
            ];
        }

        return [
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'Profile details submitted',
            'data' => $user
        ];
    }

    public static function verifyAndSaveSPDetails($request)
    {
        $mobile = $request->input('mobile1_number');
        $token  = $request->input('token');

        $tokenCheck = AuthHelper::validateToken('s_p_users', $mobile, $token, 'mobile1_number');
        if (!$tokenCheck['valid']) {
            return [
                'status' => 'failure',
                'status_code' => $tokenCheck['status_code'],
                'status_message' => $tokenCheck['message'],
                'data' => null
            ];
        }
        $user = $tokenCheck['user'];


        // Validation
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'mobile1_number'   => 'required|string|regex:/^[0-9]{10,15}$/',
            'whatsapp'         => 'required|string|regex:/^[0-9]{10,15}$/',
            'email' => 'required|email:rfc,dns|max:255',
            'dob'              => 'required|date_format:d-m-Y', // 👈 correct format
            'intrested_role'   => 'required',
            'pincode'          => 'required|string|max:10',
            'state'            => 'required|string|max:255',
            'city'             => 'required|string|max:255',
            'country'          => 'required|string|max:255',
            'profile_photo'    => 'nullable|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => 'Validation failed',
                'data' => $validator->errors()
            ];
        }

        // ✅ Age Validation (must be >= 18)
        try {
            $dob = Carbon::createFromFormat('d-m-Y', $request->dob);
            $age = $dob->age;

            if ($age < 18) {
                return [
                    'status' => 'failure',
                    'status_code' => 422,
                    'status_message' => 'Age must be 18 or above.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => 'Invalid DOB format. Please use d-m-Y.',
                'data' => null
            ];
        }


        $postData = $request->only([
            'first_name',
            'last_name',
            'email',
            'mobile1_number',
            'whatsapp',
            'dob',
            'gender',
            'profile_status',
            'prior_experience',
            'intrested_role',
            'pincode',
            'state',
            'country',
            'city',
            'coverage_radius',
            'latitude',
            'longitude',
            'has_two_wheeler',
            'address',
            'referral_id',
            'referral_url'
        ]);

        // $zohopostData = $request->only([
        //     'first_name',
        //     'last_name',
        //     'email',
        //     'mobile1_number',
        //     'whatsapp',
        //     'dob',
        //     'gender',
        //     'profile_status',
        //     'prior_experience',
        //     'intrested_role',
        //     'pincode',
        //     'state',
        //     'country',
        //     'city',
        // ]);

        // Handle file upload if exists
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $timestamp = now()->format('YmdHis');
            $fileName = 'profile_pic_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $folderPath = 'hepstrr_doc/SP/profile_pics/sp_' . $user->id . '/';

            $aw = new AWSHelper();

            $image_path = $aw->upload_file_in_s3($folderPath, $fileName, file_get_contents($file));
            $postData['profile_picture'] = $image_path;
        }

        // Encode array fields
        if (isset($postData['prior_experience']) && is_array($postData['prior_experience'])) {
            $postData['prior_experience'] = json_encode($postData['prior_experience']);
        }
        if (isset($postData['intrested_role']) && is_array($postData['intrested_role'])) {
            $postData['intrested_role'] = json_encode($postData['intrested_role']);
        }

        try {
            DB::beginTransaction();

            // Check for duplicate mobile
            if ($postData['mobile1_number'] !== $user->mobile1_number) {
                $phoneOwner = DB::table('s_p_users')
                    ->where('mobile1_number', $postData['mobile1_number'])
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($phoneOwner) {
                    DB::rollBack();
                    return [
                        'status' => 'failure',
                        'status_code' => 409,
                        'status_message' => 'Mobile number already exists for another user',
                        'data' => null
                    ];
                }
            }

            // Check for duplicate email
            $emailOwner = DB::table('s_p_users')
                ->where('email', $postData['email'])
                ->where('id', '!=', $user->id)
                ->first();

            if ($emailOwner) {
                DB::rollBack();
                return [
                    'status' => 'failure',
                    'status_code' => 409,
                    'status_message' => 'Email already exists for another user',
                    'data' => null
                ];
            }

            // Update user
            DB::table('s_p_users')->where('id', $user->id)->update($postData);

            // Sync with Zoho
            // try {
            //     $zh = new ZohoHelper();
            //     $phone = preg_replace('/\D/', '', $request->input('mobile1_number', ''));
            //     $data = $zh->getSPDetails($phone);

            //     if (isset($data['message']) && stripos(trim($data['message']), "No records found") !== false) {
            //         $zh->insertSp($zohopostData);
            //     } else {
            //         $recordId = $data['data'][0]['ID'] ?? null;
            //         if ($recordId) {
            //             $zh->updateSp($recordId, $zohopostData);
            //         }
            //     }

            //     DB::table('s_p_users')->where('id', $user->id)->update(['zoho_pushed_status' => 'true']);
            // } catch (\Throwable $e) {
            //     DB::table('s_p_users')->where('id', $user->id)->update(['zoho_pushed_status' => 'false']);
            // }

            DB::commit();


            // ✅ Call the Referral Service to generate referral code & link



          //  $referralResponse = ReferralService::createReferralForUser($user);


            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'SP details updated successfully',
                'data' => $user
            ];
        } catch (\Exception $e) {

            DB::rollBack();
            return [
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Something went wrong while updating SP details',
                'data' => null
            ];
        }
    }


}
