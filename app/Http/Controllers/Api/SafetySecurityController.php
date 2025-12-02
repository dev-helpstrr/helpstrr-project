<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Models\Task;
use App\Models\Customer;
use App\Models\ServiceProvider;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class SafetySecurityController extends Controller
{
    /**
     * Trigger panic button / SOS alert
     */
    public function triggerPanicButton(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:customer,service_provider',
            'user_id' => 'required|integer',
            'task_id' => 'sometimes|integer|exists:tasks,id',
            'alert_type' => 'required|in:panic_button,sos,safety_concern,emergency',
            'description' => 'sometimes|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_address' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify user exists
            if ($request->user_type === 'customer') {
                $user = Customer::find($request->user_id);
            } else {
                $user = ServiceProvider::find($request->user_id);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Create emergency alert
            $alert = EmergencyAlert::create([
                'user_type' => $request->user_type,
                'user_id' => $request->user_id,
                'task_id' => $request->task_id,
                'alert_type' => $request->alert_type,
                'description' => $request->description,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
                'status' => 'active',
                'triggered_at' => now(),
                'metadata' => [
                    'user_agent' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'timestamp' => now()->toISOString()
                ]
            ]);

            // Log the emergency alert
            Log::critical("Emergency alert triggered", [
                'alert_id' => $alert->id,
                'user_type' => $request->user_type,
                'user_id' => $request->user_id,
                'alert_type' => $request->alert_type,
                'location' => [$request->latitude, $request->longitude],
                'task_id' => $request->task_id
            ]);

            // TODO: Send immediate notifications to:
            // 1. Admin dashboard (real-time)
            // 2. Emergency contacts
            // 3. Local authorities if required
            // 4. Customer/SP emergency contacts

            return response()->json([
                'success' => true,
                'message' => 'Emergency alert triggered successfully',
                'data' => [
                    'alert_id' => $alert->id,
                    'status' => 'active',
                    'emergency_contact' => '+91-911', // Emergency helpline
                    'reference_number' => 'EMG-' . str_pad($alert->id, 6, '0', STR_PAD_LEFT)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to trigger panic button: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger emergency alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emergency alert status
     */
    public function getAlertStatus(Request $request, $alertId)
    {
        try {
            $alert = EmergencyAlert::find($alertId);
            
            if (!$alert) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alert not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'alert' => $alert,
                    'reference_number' => 'EMG-' . str_pad($alert->id, 6, '0', STR_PAD_LEFT),
                    'time_elapsed' => now()->diffInMinutes($alert->triggered_at) . ' minutes'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alert status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify user identity (Aadhaar-based verification)
     */
    public function verifyIdentity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:customer,service_provider',
            'user_id' => 'required|integer',
            'aadhaar_number' => 'required|string|size:12',
            'verification_type' => 'required|in:basic,biometric,otp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user
            if ($request->user_type === 'customer') {
                $user = Customer::find($request->user_id);
            } else {
                $user = ServiceProvider::find($request->user_id);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Mask Aadhaar for security (show only last 4 digits)
            $maskedAadhaar = $this->maskAadhaar($request->aadhaar_number);
            
            // TODO: Integrate with actual Aadhaar verification API
            // For now, simulate verification
            $verificationResult = $this->simulateAadhaarVerification($request->aadhaar_number, $request->verification_type);

            // Log verification attempt
            AdminActionLog::logAction(
                null,
                'identity_verification',
                $request->user_type === 'customer' ? 'Customer' : 'ServiceProvider',
                $user->id,
                "Identity verification attempted for {$maskedAadhaar} - Result: {$verificationResult['status']}"
            );

            return response()->json([
                'success' => $verificationResult['success'],
                'message' => $verificationResult['message'],
                'data' => [
                    'verification_id' => uniqid('VER_'),
                    'status' => $verificationResult['status'],
                    'masked_aadhaar' => $maskedAadhaar,
                    'verification_type' => $request->verification_type,
                    'verified_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Identity verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get safety guidelines and tips
     */
    public function getSafetyGuidelines(Request $request)
    {
        $userType = $request->get('user_type', 'general');
        
        $guidelines = [
            'general' => [
                'title' => 'General Safety Guidelines',
                'tips' => [
                    'Always verify the identity of service providers before allowing entry',
                    'Keep emergency contacts readily available',
                    'Share your location with trusted contacts during service',
                    'Trust your instincts - if something feels wrong, use the panic button',
                    'Keep your phone charged and accessible',
                    'Avoid sharing personal financial information'
                ]
            ],
            'customer' => [
                'title' => 'Customer Safety Guidelines',
                'tips' => [
                    'Verify SP identity using the app before opening your door',
                    'Check SP ratings and reviews before booking',
                    'Stay present during the service when possible',
                    'Use the in-app chat for communication',
                    'Report any inappropriate behavior immediately',
                    'Keep valuables secure during service'
                ]
            ],
            'service_provider' => [
                'title' => 'Service Provider Safety Guidelines',
                'tips' => [
                    'Always carry valid ID and verification documents',
                    'Inform customers before arriving at their location',
                    'Use protective equipment when necessary',
                    'Report unsafe working conditions',
                    'Maintain professional behavior at all times',
                    'Use the panic button if you feel threatened'
                ]
            ],
            'women_safety' => [
                'title' => 'Women Safety Guidelines',
                'tips' => [
                    'Request female service providers when available',
                    'Have a trusted contact on standby during service',
                    'Use video calls to verify SP identity',
                    'Keep doors unlocked for quick exit if needed',
                    'Trust your instincts and prioritize your safety',
                    'Use the women safety features in the app'
                ]
            ]
        ];

        $emergencyContacts = [
            'police' => '100',
            'women_helpline' => '1091',
            'emergency_services' => '108',
            'helpstrr_support' => '+91-XXXXXXXXXX'
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'guidelines' => $guidelines[$userType] ?? $guidelines['general'],
                'emergency_contacts' => $emergencyContacts,
                'safety_features' => [
                    'panic_button' => 'Instantly alert authorities and emergency contacts',
                    'live_tracking' => 'Share real-time location with trusted contacts',
                    'identity_verification' => 'Verify service provider identity',
                    'safe_chat' => 'Communicate through secure in-app messaging',
                    'rating_system' => 'Check and provide feedback on service providers'
                ]
            ]
        ]);
    }

    /**
     * Report safety incident
     */
    public function reportIncident(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reporter_type' => 'required|in:customer,service_provider',
            'reporter_id' => 'required|integer',
            'incident_type' => 'required|in:harassment,theft,inappropriate_behavior,safety_violation,other',
            'task_id' => 'sometimes|integer|exists:tasks,id',
            'description' => 'required|string|max:1000',
            'evidence_files' => 'sometimes|array',
            'evidence_files.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4|max:10240', // 10MB max
            'incident_date' => 'required|date',
            'location' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store evidence files securely
            $evidenceFiles = [];
            if ($request->hasFile('evidence_files')) {
                foreach ($request->file('evidence_files') as $file) {
                    $evidenceFiles[] = $file->store('incident-evidence', 'private');
                }
            }

            // Create incident report (using Issue model for now)
            $incident = \App\Models\Issue::create([
                'reporter_type' => $request->reporter_type,
                'reporter_id' => $request->reporter_id,
                'task_id' => $request->task_id,
                'category' => 'safety',
                'title' => 'Safety Incident: ' . ucfirst(str_replace('_', ' ', $request->incident_type)),
                'description' => $request->description,
                'priority' => 'high', // Safety incidents are high priority
                'status' => 'open',
                'metadata' => [
                    'incident_type' => $request->incident_type,
                    'incident_date' => $request->incident_date,
                    'location' => $request->location,
                    'evidence_files' => $evidenceFiles,
                    'reported_at' => now()->toISOString()
                ]
            ]);

            // Log the incident
            Log::warning("Safety incident reported", [
                'incident_id' => $incident->id,
                'type' => $request->incident_type,
                'reporter' => $request->reporter_type . '_' . $request->reporter_id,
                'task_id' => $request->task_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Incident reported successfully',
                'data' => [
                    'incident_id' => $incident->id,
                    'reference_number' => 'INC-' . str_pad($incident->id, 6, '0', STR_PAD_LEFT),
                    'status' => 'under_review',
                    'expected_response_time' => '24 hours'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report incident',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get women safety features
     */
    public function getWomenSafetyFeatures(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'features' => [
                    'female_sp_preference' => [
                        'title' => 'Female Service Provider Preference',
                        'description' => 'Request female service providers when available',
                        'enabled' => true
                    ],
                    'trusted_contacts' => [
                        'title' => 'Trusted Contacts',
                        'description' => 'Add up to 3 emergency contacts who will be notified',
                        'enabled' => true
                    ],
                    'live_location_sharing' => [
                        'title' => 'Live Location Sharing',
                        'description' => 'Share real-time location with trusted contacts',
                        'enabled' => true
                    ],
                    'video_verification' => [
                        'title' => 'Video Verification',
                        'description' => 'Video call with SP before service starts',
                        'enabled' => true
                    ],
                    'safe_hours' => [
                        'title' => 'Safe Hours Booking',
                        'description' => 'Prefer bookings during daylight hours',
                        'enabled' => true,
                        'safe_hours' => '06:00 - 20:00'
                    ],
                    'quick_exit' => [
                        'title' => 'Quick Exit Protocol',
                        'description' => 'One-tap emergency exit with instant alerts',
                        'enabled' => true
                    ]
                ],
                'emergency_contacts' => [
                    'women_helpline' => '1091',
                    'police' => '100',
                    'helpstrr_women_safety' => '+91-XXXXXXXXXX'
                ],
                'safety_tips' => [
                    'Always verify SP identity before opening the door',
                    'Keep a trusted contact informed about your bookings',
                    'Use the in-app communication features',
                    'Trust your instincts and prioritize your safety',
                    'Report any inappropriate behavior immediately'
                ]
            ]
        ]);
    }

    /**
     * Mask Aadhaar number for security (show only last 4 digits)
     */
    private function maskAadhaar($aadhaarNumber)
    {
        if (strlen($aadhaarNumber) !== 12) {
            return 'XXXX-XXXX-XXXX';
        }
        
        return 'XXXX-XXXX-' . substr($aadhaarNumber, -4);
    }

    /**
     * Simulate Aadhaar verification (replace with actual API integration)
     */
    private function simulateAadhaarVerification($aadhaarNumber, $verificationType)
    {
        // Simulate different verification results
        $random = rand(1, 100);
        
        if ($random <= 85) { // 85% success rate
            return [
                'success' => true,
                'status' => 'verified',
                'message' => 'Identity verified successfully'
            ];
        } elseif ($random <= 95) { // 10% pending
            return [
                'success' => false,
                'status' => 'pending',
                'message' => 'Verification in progress. Please try again in a few minutes.'
            ];
        } else { // 5% failed
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Identity verification failed. Please check your details.'
            ];
        }
    }
}
