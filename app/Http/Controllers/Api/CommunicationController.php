<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Customer;
use App\Models\ServiceProvider;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CommunicationController extends Controller
{
    /**
     * Send push notification
     */
    public function sendPushNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:customer,service_provider,admin',
            'user_id' => 'required|integer',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'type' => 'required|in:task_update,payment,emergency,promotion,general',
            'data' => 'sometimes|array',
            'priority' => 'sometimes|in:low,normal,high,critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user device tokens
            $deviceTokens = $this->getUserDeviceTokens($request->user_type, $request->user_id);
            
            if (empty($deviceTokens)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No device tokens found for user'
                ], 404);
            }

            // Prepare notification payload
            $notificationData = [
                'title' => $request->title,
                'body' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority ?? 'normal',
                'data' => $request->data ?? [],
                'timestamp' => now()->toISOString()
            ];

            // Send push notification
            $result = $this->sendFirebasePushNotification($deviceTokens, $notificationData);
            
            // Store notification in database
            $this->storeNotification([
                'user_type' => $request->user_type,
                'user_id' => $request->user_id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'data' => $notificationData,
                'sent_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push notification sent successfully',
                'data' => [
                    'notification_id' => uniqid('NOTIF_'),
                    'sent_to_devices' => count($deviceTokens),
                    'delivery_status' => $result['success'] ? 'sent' : 'failed'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send push notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send SMS notification
     */
    public function sendSMS(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'message' => 'required|string|max:160',
            'type' => 'required|in:otp,task_update,emergency,promotional',
            'template_id' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Send SMS via SMS gateway
            $result = $this->sendSMSViaGateway($request->phone_number, $request->message, $request->type);
            
            // Log SMS sending
            Log::info("SMS sent", [
                'phone' => $request->phone_number,
                'type' => $request->type,
                'status' => $result['success'] ? 'sent' : 'failed'
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'sms_id' => $result['sms_id'] ?? uniqid('SMS_'),
                    'phone_number' => $this->maskPhoneNumber($request->phone_number),
                    'delivery_status' => $result['success'] ? 'sent' : 'failed',
                    'sent_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send SMS: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'message' => 'required|string|max:1000',
            'template_name' => 'sometimes|string',
            'template_params' => 'sometimes|array',
            'media_url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Send WhatsApp message via WhatsApp Business API
            $result = $this->sendWhatsAppMessage($request->all());
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'whatsapp_id' => $result['message_id'] ?? uniqid('WA_'),
                    'phone_number' => $this->maskPhoneNumber($request->phone_number),
                    'delivery_status' => $result['success'] ? 'sent' : 'failed',
                    'sent_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send WhatsApp message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make IVR call
     */
    public function makeIVRCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'call_type' => 'required|in:task_reminder,emergency_alert,verification,promotional',
            'message_template' => 'required|string',
            'language' => 'sometimes|in:en,hi,bn'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Make IVR call via telephony service
            $result = $this->makeIVRCallViaService($request->all());
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'call_id' => $result['call_id'] ?? uniqid('IVR_'),
                    'phone_number' => $this->maskPhoneNumber($request->phone_number),
                    'call_status' => $result['status'] ?? 'initiated',
                    'initiated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to make IVR call: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to make IVR call',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send task-related notifications
     */
    public function sendTaskNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'notification_type' => 'required|in:task_assigned,sp_on_way,sp_arrived,task_started,task_completed,payment_received',
            'recipient' => 'required|in:customer,service_provider,both'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::with(['customer', 'serviceProvider'])->find($request->task_id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $notifications = $this->prepareTaskNotifications($task, $request->notification_type, $request->recipient);
            $results = [];

            foreach ($notifications as $notification) {
                // Send push notification
                $pushResult = $this->sendPushNotification(new Request($notification['push']));
                
                // Send SMS if required
                if (isset($notification['sms'])) {
                    $smsResult = $this->sendSMS(new Request($notification['sms']));
                }
                
                $results[] = [
                    'recipient' => $notification['recipient'],
                    'push_sent' => $pushResult->getData()->success ?? false,
                    'sms_sent' => isset($smsResult) ? $smsResult->getData()->success ?? false : false
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Task notifications sent successfully',
                'data' => [
                    'task_id' => $task->id,
                    'notification_type' => $request->notification_type,
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send task notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's notification history
     */
    public function getNotificationHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:customer,service_provider',
            'user_id' => 'required|integer',
            'limit' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->get('limit', 20);
            $type = $request->get('type');

            $query = Notification::where('user_type', $request->user_type)
                ->where('user_id', $request->user_id);

            if ($type) {
                $query->where('type', $type);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'total_count' => $notifications->count(),
                    'unread_count' => $notifications->where('read_at', null)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user device tokens for push notifications
     */
    private function getUserDeviceTokens($userType, $userId)
    {
        // This would typically fetch from a user_devices table
        // For now, return mock tokens
        return ['mock_device_token_' . $userType . '_' . $userId];
    }

    /**
     * Send Firebase push notification
     */
    private function sendFirebasePushNotification($deviceTokens, $notificationData)
    {
        // TODO: Integrate with Firebase Cloud Messaging
        // For now, simulate successful sending
        Log::info("Push notification sent", [
            'tokens' => count($deviceTokens),
            'title' => $notificationData['title']
        ]);

        return ['success' => true, 'message' => 'Notification sent'];
    }

    /**
     * Send SMS via SMS gateway
     */
    private function sendSMSViaGateway($phoneNumber, $message, $type)
    {
        // TODO: Integrate with SMS gateway (Twilio, AWS SNS, etc.)
        // For now, simulate successful sending
        Log::info("SMS sent", [
            'phone' => $this->maskPhoneNumber($phoneNumber),
            'type' => $type
        ]);

        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'sms_id' => uniqid('SMS_')
        ];
    }

    /**
     * Send WhatsApp message
     */
    private function sendWhatsAppMessage($data)
    {
        // TODO: Integrate with WhatsApp Business API
        // For now, simulate successful sending
        Log::info("WhatsApp message sent", [
            'phone' => $this->maskPhoneNumber($data['phone_number'])
        ]);

        return [
            'success' => true,
            'message' => 'WhatsApp message sent successfully',
            'message_id' => uniqid('WA_')
        ];
    }

    /**
     * Make IVR call via telephony service
     */
    private function makeIVRCallViaService($data)
    {
        // TODO: Integrate with telephony service (Twilio, Exotel, etc.)
        // For now, simulate successful call initiation
        Log::info("IVR call initiated", [
            'phone' => $this->maskPhoneNumber($data['phone_number']),
            'type' => $data['call_type']
        ]);

        return [
            'success' => true,
            'message' => 'IVR call initiated successfully',
            'call_id' => uniqid('IVR_'),
            'status' => 'initiated'
        ];
    }

    /**
     * Store notification in database
     */
    private function storeNotification($data)
    {
        // TODO: Store in notifications table
        Log::info("Notification stored", $data);
    }

    /**
     * Prepare task-specific notifications
     */
    private function prepareTaskNotifications($task, $notificationType, $recipient)
    {
        $notifications = [];
        
        $templates = [
            'task_assigned' => [
                'customer' => [
                    'title' => 'Service Provider Assigned',
                    'message' => "Your {$task->subcategory->name} service has been assigned to {$task->serviceProvider->name}."
                ],
                'service_provider' => [
                    'title' => 'New Task Assigned',
                    'message' => "You have been assigned a new {$task->subcategory->name} task."
                ]
            ],
            'sp_on_way' => [
                'customer' => [
                    'title' => 'Service Provider On The Way',
                    'message' => "{$task->serviceProvider->name} is on the way to your location."
                ]
            ],
            'sp_arrived' => [
                'customer' => [
                    'title' => 'Service Provider Arrived',
                    'message' => "{$task->serviceProvider->name} has arrived at your location."
                ]
            ],
            'task_completed' => [
                'customer' => [
                    'title' => 'Service Completed',
                    'message' => "Your {$task->subcategory->name} service has been completed. Please rate your experience."
                ],
                'service_provider' => [
                    'title' => 'Task Completed',
                    'message' => "Task completed successfully. Payment will be processed shortly."
                ]
            ]
        ];

        $template = $templates[$notificationType] ?? [];
        
        if ($recipient === 'customer' || $recipient === 'both') {
            if (isset($template['customer'])) {
                $notifications[] = [
                    'recipient' => 'customer',
                    'push' => [
                        'user_type' => 'customer',
                        'user_id' => $task->customer_id,
                        'title' => $template['customer']['title'],
                        'message' => $template['customer']['message'],
                        'type' => 'task_update',
                        'data' => ['task_id' => $task->id]
                    ]
                ];
            }
        }

        if ($recipient === 'service_provider' || $recipient === 'both') {
            if (isset($template['service_provider'])) {
                $notifications[] = [
                    'recipient' => 'service_provider',
                    'push' => [
                        'user_type' => 'service_provider',
                        'user_id' => $task->service_provider_id,
                        'title' => $template['service_provider']['title'],
                        'message' => $template['service_provider']['message'],
                        'type' => 'task_update',
                        'data' => ['task_id' => $task->id]
                    ]
                ];
            }
        }

        return $notifications;
    }

    /**
     * Mask phone number for privacy
     */
    private function maskPhoneNumber($phoneNumber)
    {
        if (strlen($phoneNumber) >= 10) {
            return substr($phoneNumber, 0, 2) . 'XXXXXX' . substr($phoneNumber, -2);
        }
        return 'XXXXXXXXXX';
    }
}
