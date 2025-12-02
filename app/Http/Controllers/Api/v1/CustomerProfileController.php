<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Helpers\AWSHelper;
use App\Helpers\AuthHelper;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\API\CustomerSendOtpRequest;
use App\Http\Requests\API\CustomerVerifyOtpRequest;
use App\Http\Requests\API\CustomerProfileRequest;

class CustomerProfileController extends Controller
{
    /**
     * STEP 1 — Send OTP (same as SP)
     */
    public function sendOtp(CustomerSendOtpRequest $request): JsonResponse
    {
        $phone = $request->phone;

        // find or create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            [
                'email' => $phone . '@temp.helpstrr.com' // temporary email
            ]
        );

        // generate OTP (SP style)
        $otp = AuthHelper::generateOtp();

        // store in DB exactly like SP
        $customer->login_otp = $otp;
        $customer->save();

        // send OTP exactly like SP
        $nh = new NotificationHelper();
        $nh->sendNotification('whatsapp', [
            'phone' => $phone,
            'otp'   => $otp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'phone'   => $phone,
            'otp'   => $otp,
        ], 200);
    }


    /**
     * STEP 2 — Verify OTP (same as SP)
     */
    public function verifyOtp(CustomerVerifyOtpRequest $request): JsonResponse
    {

        $phone = $request->phone;
        $otp   = $request->otp;

        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // SP-style OTP validation
        if ($customer->login_otp != $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        $token = $customer->generateApiToken('auth_token');
        // mark verified & clear OTP
        $customer->mobile_verified = true;
        $customer->login_otp = null;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'customer_id' => $customer->id,
            'token' => $token
        ], 200);
    }


    /**
     * STEP 3 — Update Profile (multi-address)
     */
    public function profileDetails(CustomerProfileRequest $request)
    {


        $phone = $request->phone;
        $token  = $request->input('token');
        $tokenCheck = AuthHelper::validateToken('customers', $phone, $token, 'phone');

        if (!$tokenCheck['valid']) {

            return response()->json([
                'status' => 'failure',
                'status_code' => $tokenCheck['status_code'],
                'status_message' => $tokenCheck['message'],
                'data' => null
            ], $tokenCheck['status_code']);
        }

        $user = $tokenCheck['user'];
        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $data = $request->only([
            'name',
            'email',
            'latitude',
            'longitude'
        ]);

        // handle profile photo upload
        if ($request->hasFile('profile_photo')) {

            $file = $request->file('profile_photo');
            $timestamp = now()->format('YmdHis');
            $fileName = 'profile_pic_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $folderPath = 'hepstrr_doc/customers/profile_pics/customer_' . $user->id . '/';

            $aw = new AWSHelper();

            $image_path = $aw->upload_file_in_s3($folderPath, $fileName, file_get_contents($file));
            $data['profile_photo_url'] = $image_path;
        }


        $customer->update($data);

        // save multiple addresses
        if ($request->filled('addresses')) {
            CustomerAddress::where('customer_id', $customer->id)->delete();

            foreach ($request->addresses as $addr) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'type'        => $addr['type'] ?? null,
                    'address_line_1' => $addr['address_line1'] ?? null,
                    'address_line_2' => $addr['address_line2'] ?? null,
                    'city'        => $addr['city'] ?? null,
                    'state'       => $addr['state'] ?? null,
                    'pincode'    => $addr['zip_code'] ?? null,
                    'country'     => $addr['country'] ?? 'India',
                    'latitude'    => $addr['latitude'] ?? null,
                    'longitude'   => $addr['longitude'] ?? null,
                ]);
            }
        }

        $customer->load('addresses');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => $customer
        ], 200);
    }

    public static function getCustomerDetails(Request $request)
    {
        $user = Customer::where('phone', $request->phone)->first();

        if (! $user) {
            return [
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'Phone number not found',
                'data' => null
            ];
        }

        if ($user->token !== $request->token) {
            return [
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Invalid token',
                'data' => null
            ];
        }

        // Load customer addresses using relationship
        $user->addresses = CustomerAddress::where('customer_id', $user->id)->get();

        // Mandatory fields
        $mandatoryFields = [
            'name',
            'phone',
            'mobile_verified',
            'whatsapp',
            'email',
            'profile_photo_url',
            'intrested_role',
            'latitude',
            'longitude',
            's_active'
        ];

        $allFilled = true;

        foreach ($mandatoryFields as $field) {
            if (empty($user->$field)) {
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

    /**
     * Simple login method for testing
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string'
        ]);

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || !password_verify($request->password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$customer->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Generate token
        $token = $customer->generateApiToken('auth_token');
        $customer->last_login = now();
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => $customer->makeHidden(['password']),
                'token' => $token
            ]
        ]);
    }
}
