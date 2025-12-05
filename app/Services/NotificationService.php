<?php

namespace App\Services;

use App\Models\Task;
use App\Models\ServiceProvider;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send task status notification
     * 
     * @param Task $task
     * @param string $status
     * @return void
     */
    public function sendTaskStatusNotification(Task $task, string $status): void
    {
        try {
            $customer = $task->customer;
            $serviceProvider = $task->serviceProvider;

            // Prepare notification data
            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'status' => $status,
                'status_label' => $task->status_badge['text'],
                'customer_name' => $customer->name,
                'sp_name' => $serviceProvider ? $serviceProvider->spUser->name : null,
            ];

            // Send to customer
            $this->sendToCustomer($customer, $this->getCustomerStatusMessage($status, $notificationData), $notificationData);

            // Send to service provider (if assigned)
            if ($serviceProvider) {
                $this->sendToServiceProvider($serviceProvider, $this->getSPStatusMessage($status, $notificationData), $notificationData);
            }

            // Log notification
            $this->logNotification('task_status_update', $task->id, $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send task status notification: " . $e->getMessage());
        }
    }

    /**
     * Send task assignment notification
     * 
     * @param Task $task
     * @return void
     */
    public function sendTaskAssignmentNotification(Task $task): void
    {
        try {
            $customer = $task->customer;
            $serviceProvider = $task->serviceProvider;

            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'customer_name' => $customer->name,
                'sp_name' => $serviceProvider->spUser->name,
                'sp_phone' => $serviceProvider->spUser->phone,
                'sp_rating' => $serviceProvider->rating,
                'scheduled_at' => $task->scheduled_at->format('d M Y, h:i A'),
            ];

            // Send to customer
            $customerMessage = "Great news! {$serviceProvider->spUser->name} has been assigned to your booking #{$task->task_number}. They will arrive at {$task->scheduled_at->format('h:i A')} on {$task->scheduled_at->format('d M Y')}.";
            $this->sendToCustomer($customer, $customerMessage, $notificationData);

            // Send to service provider
            $spMessage = "You have been assigned a new task #{$task->task_number}. Customer: {$customer->name}. Scheduled for {$task->scheduled_at->format('d M Y, h:i A')}.";
            $this->sendToServiceProvider($serviceProvider, $spMessage, $notificationData);

            // Log notification
            $this->logNotification('task_assignment', $task->id, $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send task assignment notification: " . $e->getMessage());
        }
    }

    /**
     * Send task broadcast notifications to service providers
     * 
     * @param Task $task
     * @param Collection $providers
     * @return void
     */
    public function sendTaskBroadcastNotifications(Task $task, Collection $providers): void
    {
        try {
            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'category' => $task->category->name,
                'subcategory' => $task->subcategory->name,
                'service' => $task->service->name,
                'pax_count' => $task->pax_count,
                'requested_hours' => $task->requested_hours,
                'scheduled_at' => $task->scheduled_at->format('d M Y, h:i A'),
                'customer_area' => $task->customerAddress->city,
                'estimated_payout' => $task->total_amount * 0.8, // 80% to SP
            ];

            foreach ($providers as $provider) {
                $message = "New task available! {$task->category->name} service for {$task->requested_hours} hours. Payout: ₹" . number_format($notificationData['estimated_payout'], 2) . ". Tap to accept.";
                
                $this->sendToServiceProvider($provider, $message, $notificationData);
            }

            // Log notification
            $this->logNotification('task_broadcast', $task->id, array_merge($notificationData, [
                'provider_count' => $providers->count(),
                'provider_ids' => $providers->pluck('id')->toArray(),
            ]));

        } catch (\Exception $e) {
            Log::error("Failed to send task broadcast notifications: " . $e->getMessage());
        }
    }

    /**
     * Send task cancellation notification
     * 
     * @param Task $task
     * @param float $cancellationCharges
     * @return void
     */
    public function sendTaskCancellationNotification(Task $task, float $cancellationCharges): void
    {
        try {
            $customer = $task->customer;
            $serviceProvider = $task->serviceProvider;

            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'cancelled_by' => $task->cancelled_by,
                'cancellation_reason' => $task->cancellation_reason,
                'cancellation_charges' => $cancellationCharges,
                'refund_amount' => max(0, $task->final_amount - $cancellationCharges),
            ];

            // Send to customer
            if ($task->cancelled_by === 'customer') {
                $customerMessage = "Your booking #{$task->task_number} has been cancelled. ";
                if ($cancellationCharges > 0) {
                    $customerMessage .= "Cancellation charges: ₹" . number_format($cancellationCharges, 2) . ". ";
                    $customerMessage .= "Refund amount: ₹" . number_format($notificationData['refund_amount'], 2) . " will be processed within 3-5 business days.";
                } else {
                    $customerMessage .= "Full refund of ₹" . number_format($task->final_amount, 2) . " will be processed within 3-5 business days.";
                }
            } else {
                $customerMessage = "Unfortunately, your booking #{$task->task_number} has been cancelled by the service provider. We are finding you an alternative. Full refund will be processed if we cannot reassign.";
            }
            
            $this->sendToCustomer($customer, $customerMessage, $notificationData);

            // Send to service provider (if assigned and not the one who cancelled)
            if ($serviceProvider && $task->cancelled_by !== 'service_provider') {
                $spMessage = "Task #{$task->task_number} has been cancelled by the customer. You will receive compensation for any travel expenses incurred.";
                $this->sendToServiceProvider($serviceProvider, $spMessage, $notificationData);
            }

            // Log notification
            $this->logNotification('task_cancellation', $task->id, $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send task cancellation notification: " . $e->getMessage());
        }
    }

    /**
     * Send task rating notification
     * 
     * @param Task $task
     * @param string $ratedBy
     * @return void
     */
    public function sendTaskRatingNotification(Task $task, string $ratedBy): void
    {
        try {
            $customer = $task->customer;
            $serviceProvider = $task->serviceProvider;

            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'rated_by' => $ratedBy,
                'customer_rating' => $task->customer_rating,
                'customer_feedback' => $task->customer_feedback,
                'sp_rating' => $task->sp_rating,
                'sp_feedback' => $task->sp_feedback,
            ];

            if ($ratedBy === 'customer') {
                // Notify service provider about customer rating
                $spMessage = "You received a {$task->customer_rating}-star rating from {$customer->name} for task #{$task->task_number}.";
                if ($task->customer_feedback) {
                    $spMessage .= " Feedback: \"{$task->customer_feedback}\"";
                }
                $this->sendToServiceProvider($serviceProvider, $spMessage, $notificationData);
            } else {
                // Notify customer about SP rating
                $customerMessage = "Thank you for rating your service provider. Your feedback helps us improve our services.";
                $this->sendToCustomer($customer, $customerMessage, $notificationData);
            }

            // Log notification
            $this->logNotification('task_rating', $task->id, $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send task rating notification: " . $e->getMessage());
        }
    }

    /**
     * Send no providers available notification
     * 
     * @param Task $task
     * @return void
     */
    public function sendNoProvidersNotification(Task $task): void
    {
        try {
            $customer = $task->customer;

            $notificationData = [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'scheduled_at' => $task->scheduled_at->format('d M Y, h:i A'),
            ];

            $customerMessage = "We're sorry, but no service providers are currently available for your booking #{$task->task_number} scheduled for {$task->scheduled_at->format('d M Y, h:i A')}. We're working to find alternatives and will update you soon. You can also reschedule or cancel without charges.";
            
            $this->sendToCustomer($customer, $customerMessage, $notificationData);

            // Log notification
            $this->logNotification('no_providers_available', $task->id, $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send no providers notification: " . $e->getMessage());
        }
    }

    /**
     * Send notification to customer
     * 
     * @param \App\Models\Customer $customer
     * @param string $message
     * @param array $data
     * @return void
     */
    private function sendToCustomer($customer, string $message, array $data): void
    {
        // Send push notification
        $this->sendPushNotification($customer->id, 'customer', $message, $data);

        // Send SMS
        if ($customer->phone) {
            $this->sendSMS($customer->phone, $message);
        }

        // Send WhatsApp (if enabled)
        if ($customer->phone && $this->isWhatsAppEnabled()) {
            $this->sendWhatsApp($customer->phone, $message);
        }
    }

    /**
     * Send notification to service provider
     * 
     * @param ServiceProvider $serviceProvider
     * @param string $message
     * @param array $data
     * @return void
     */
    private function sendToServiceProvider(ServiceProvider $serviceProvider, string $message, array $data): void
    {
        $spUser = $serviceProvider->spUser;

        // Send push notification
        $this->sendPushNotification($serviceProvider->id, 'service_provider', $message, $data);

        // Send SMS
        if ($spUser->phone) {
            $this->sendSMS($spUser->phone, $message);
        }

        // Send IVR call for urgent notifications (task broadcasts)
        if (isset($data['task_id']) && $spUser->phone) {
            $this->makeIVRCall($spUser->phone, $message);
        }
    }

    /**
     * Send push notification
     * 
     * @param int $userId
     * @param string $userType
     * @param string $message
     * @param array $data
     * @return void
     */
    private function sendPushNotification(int $userId, string $userType, string $message, array $data): void
    {
        try {
            // Implementation would depend on your push notification service (FCM, etc.)
            // For now, we'll just log it
            Log::info("Push notification sent", [
                'user_id' => $userId,
                'user_type' => $userType,
                'message' => $message,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage());
        }
    }

    /**
     * Send SMS
     * 
     * @param string $phone
     * @param string $message
     * @return void
     */
    private function sendSMS(string $phone, string $message): void
    {
        try {
            // Implementation would depend on your SMS service provider
            // For now, we'll just log it
            Log::info("SMS sent", [
                'phone' => $phone,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send SMS: " . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp message
     * 
     * @param string $phone
     * @param string $message
     * @return void
     */
    private function sendWhatsApp(string $phone, string $message): void
    {
        try {
            // Implementation would depend on your WhatsApp Business API
            // For now, we'll just log it
            Log::info("WhatsApp message sent", [
                'phone' => $phone,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message: " . $e->getMessage());
        }
    }

    /**
     * Make IVR call
     * 
     * @param string $phone
     * @param string $message
     * @return void
     */
    private function makeIVRCall(string $phone, string $message): void
    {
        try {
            // Implementation would depend on your IVR service provider
            // For now, we'll just log it
            Log::info("IVR call made", [
                'phone' => $phone,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to make IVR call: " . $e->getMessage());
        }
    }

    /**
     * Log notification
     * 
     * @param string $type
     * @param int $taskId
     * @param array $data
     * @return void
     */
    private function logNotification(string $type, int $taskId, array $data): void
    {
        try {
            NotificationLog::create([
                'type' => $type,
                'task_id' => $taskId,
                'data' => json_encode($data),
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log notification: " . $e->getMessage());
        }
    }

    /**
     * Get customer status message
     * 
     * @param string $status
     * @param array $data
     * @return string
     */
    private function getCustomerStatusMessage(string $status, array $data): string
    {
        return match($status) {
            'assigned' => "Great! {$data['sp_name']} has been assigned to your booking #{$data['task_number']}.",
            'on_the_way' => "{$data['sp_name']} is on the way to your location for booking #{$data['task_number']}.",
            'arrived' => "{$data['sp_name']} has arrived at your location. Please provide the start OTP to begin the service.",
            'started' => "Your service has started. {$data['sp_name']} is now working on your booking #{$data['task_number']}.",
            'paused' => "Your service has been temporarily paused. {$data['sp_name']} will resume shortly.",
            'resumed' => "Your service has been resumed. {$data['sp_name']} is continuing with your booking.",
            'completed' => "Your service has been completed! Please rate your experience with {$data['sp_name']}.",
            default => "Your booking #{$data['task_number']} status has been updated to {$data['status_label']}.",
        };
    }

    /**
     * Get service provider status message
     * 
     * @param string $status
     * @param array $data
     * @return string
     */
    private function getSPStatusMessage(string $status, array $data): string
    {
        return match($status) {
            'on_the_way' => "Please proceed to customer location for task #{$data['task_number']}. Mark 'Arrived' when you reach.",
            'arrived' => "Please request the start OTP from {$data['customer_name']} to begin the service.",
            'started' => "Service started for task #{$data['task_number']}. Timer is now running.",
            'paused' => "Service paused for task #{$data['task_number']}. Remember to resume when ready.",
            'resumed' => "Service resumed for task #{$data['task_number']}. Timer is running again.",
            'completed' => "Task #{$data['task_number']} completed successfully! Your earnings will be credited soon.",
            default => "Task #{$data['task_number']} status updated to {$data['status_label']}.",
        };
    }

    /**
     * Check if WhatsApp is enabled
     * 
     * @return bool
     */
    private function isWhatsAppEnabled(): bool
    {
        return config('services.whatsapp.enabled', false);
    }
}