<?php

namespace App\Http\Controllers\Api\Mobile\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    /**
     * Customer Registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string|exists:customers,referral_code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => $this->generateReferralCode(),
                'referred_by' => $request->referral_code ? 
                    Customer::where('referral_code', $request->referral_code)->first()?->id : null,
                'is_active' => true,
                'phone_verified_at' => null // Will be verified via OTP
            ]);

            // Generate access token
            $token = $customer->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'customer' => $customer->makeHidden(['password']),
                    'token' => $token,
                    'requires_phone_verification' => true
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Customer Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$customer->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated. Please contact support.'
            ], 403);
        }

        // Update last login
        $customer->update(['last_login_at' => now()]);

        // Generate access token
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => $customer->makeHidden(['password']),
                'token' => $token,
                'requires_phone_verification' => !$customer->phone_verified_at
            ]
        ]);
    }

    /**
     * Send OTP for phone verification
     */
    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $otp = rand(100000, 999999);
            $customer = Customer::where('phone', $request->phone)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Store OTP (in production, use cache or separate OTP table)
            $customer->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10)
            ]);

            // TODO: Send SMS via SMS gateway
            // For now, return OTP in response (remove in production)
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'otp' => $otp, // Remove this in production
                    'expires_in' => 600 // 10 minutes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        if ($customer->otp !== $request->otp || $customer->otp_expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark phone as verified
        $customer->update([
            'phone_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
            'data' => [
                'customer' => $customer->makeHidden(['password', 'otp'])
            ]
        ]);
    }

    /**
     * Get customer profile
     */
    public function profile(Request $request)
    {
        $customer = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer->makeHidden(['password', 'otp']),
                'addresses' => $customer->addresses,
                'stats' => [
                    'total_bookings' => $customer->tasks()->count(),
                    'completed_bookings' => $customer->tasks()->where('status', 'completed')->count(),
                    'cancelled_bookings' => $customer->tasks()->where('status', 'cancelled')->count(),
                ]
            ]
        ]);
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:customers,email,' . $customer->id,
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer->update($request->only(['name', 'email', 'date_of_birth', 'gender']));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'customer' => $customer->makeHidden(['password', 'otp'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get app configuration
     */
    public function appConfig()
    {
        $config = PlatformSetting::getPublicSettings();
        
        return response()->json([
            'success' => true,
            'data' => [
                'config' => $config,
                'app_version' => '1.0.0',
                'force_update' => false,
                'maintenance_mode' => false
            ]
        ]);
    }

    /**
     * Generate unique referral code
     */
    private function generateReferralCode()
    {
        do {
            $code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (Customer::where('referral_code', $code)->exists());

        return $code;
    }
}
