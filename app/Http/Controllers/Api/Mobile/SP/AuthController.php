<?php

namespace App\Http\Controllers\Api\Mobile\SP;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\SPKycDocument;
use App\Models\SPPerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * SP Registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string|size:6',
            'aadhaar_number' => 'required|string|size:12|unique:service_providers,aadhaar_number',
            'pan_number' => 'required|string|size:10|unique:service_providers,pan_number',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'is_active' => true,
                'phone_verified_at' => null
            ]);

            // Create service provider profile
            $serviceProvider = ServiceProvider::create([
                'user_id' => $user->id,
                'name' => $request->first_name . ' ' . $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'aadhaar_number' => $request->aadhaar_number,
                'pan_number' => $request->pan_number,
                'is_active' => false, // Will be activated after KYC
                'is_verified' => false,
                'verification_status' => 'pending'
            ]);

            // Attach categories
            $serviceProvider->categories()->attach($request->categories);

            // Initialize performance metrics
            SPPerformanceMetric::create([
                'service_provider_id' => $serviceProvider->id,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'cancelled_tasks' => 0,
                'average_rating' => 0,
                'total_earnings' => 0,
                'punctuality_score' => 0,
                'quality_score' => 0,
                'response_time_avg' => 0,
                'complaints_count' => 0,
                'last_updated' => now()
            ]);

            // Generate access token
            $token = $user->createToken('sp-mobile-app')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please complete KYC verification.',
                'data' => [
                    'user' => $user->makeHidden(['password']),
                    'service_provider' => $serviceProvider,
                    'token' => $token,
                    'requires_kyc' => true,
                    'requires_phone_verification' => true
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SP Login
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

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated. Please contact support.'
            ], 403);
        }

        $serviceProvider = $user->serviceProvider;
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);
        $serviceProvider->update(['last_seen_at' => now()]);

        // Generate access token
        $token = $user->createToken('sp-mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->makeHidden(['password']),
                'service_provider' => $serviceProvider,
                'token' => $token,
                'requires_phone_verification' => !$user->phone_verified_at,
                'requires_kyc' => !$serviceProvider->is_verified,
                'account_status' => $serviceProvider->verification_status
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
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Store OTP securely (in production, use cache or separate OTP table)
            $user->update([
                'otp' => Hash::make($otp), // Hash OTP for security
                'otp_expires_at' => now()->addMinutes(10)
            ]);

            // TODO: Send SMS via SMS gateway
            // For development, return OTP in response (remove in production)
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

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!Hash::check($request->otp, $user->otp) || $user->otp_expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark phone as verified
        $user->update([
            'phone_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
            'data' => [
                'user' => $user->makeHidden(['password', 'otp'])
            ]
        ]);
    }

    /**
     * Get SP profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $performanceMetrics = $serviceProvider->performanceMetrics;
        $kycDocuments = $serviceProvider->kycDocuments;
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->makeHidden(['password', 'otp']),
                'service_provider' => $serviceProvider,
                'performance_metrics' => $performanceMetrics,
                'kyc_documents' => $kycDocuments,
                'categories' => $serviceProvider->categories,
                'stats' => [
                    'total_tasks' => $serviceProvider->tasks()->count(),
                    'completed_tasks' => $serviceProvider->tasks()->where('status', 'completed')->count(),
                    'active_tasks' => $serviceProvider->tasks()->whereIn('status', ['assigned', 'in_progress', 'travelling'])->count(),
                    'earnings_this_month' => $serviceProvider->tasks()
                        ->where('status', 'completed')
                        ->whereMonth('completed_at', now()->month)
                        ->sum('sp_amount'),
                ]
            ]
        ]);
    }

    /**
     * Update SP profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'pincode' => 'sometimes|string|size:6',
            'bio' => 'sometimes|string|max:500',
            'experience_years' => 'sometimes|integer|min:0',
            'profile_image' => 'sometimes|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update user data
            $user->update($request->only(['first_name', 'last_name', 'email']));

            // Update service provider data
            $spData = $request->only(['address', 'city', 'state', 'pincode', 'bio', 'experience_years']);
            if ($request->hasFile('profile_image')) {
                $spData['profile_image'] = $request->file('profile_image')->store('sp-profiles', 'public');
            }
            $serviceProvider->update($spData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user->makeHidden(['password', 'otp']),
                    'service_provider' => $serviceProvider->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload KYC documents
     */
    public function uploadKYC(Request $request)
    {
        $user = $request->user();
        $serviceProvider = $user->serviceProvider;
        
        if (!$serviceProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Service provider profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:aadhaar,pan,bank_passbook,police_verification,photo,address_proof',
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'document_number' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store document file securely
            $documentPath = $request->file('document_file')->store('kyc-documents', 'private');

            // Create KYC document record
            $kycDocument = SPKycDocument::create([
                'service_provider_id' => $serviceProvider->id,
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'document_url' => $documentPath,
                'verification_status' => 'pending',
                'uploaded_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC document uploaded successfully',
                'data' => [
                    'document' => $kycDocument
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Update last seen
        if ($user->serviceProvider) {
            $user->serviceProvider->update(['last_seen_at' => now()]);
        }
        
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
