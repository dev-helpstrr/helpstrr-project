<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationLog;
use App\Models\NotificationPanel;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessNotificationJob;
use App\Models\SP\Auth\SortkarSPUser;
use App\Models\UserToken; // ensure this model exists for user_tokens table

class NotificationDetailController extends Controller
{
    // public function sendNotification(Request $request)
    // {
    //     $validated = $request->validate([
    //         'type' => 'required|string',           // Notification type (email, sms, in-app, whatsapp)
    //         'template_id' => 'required|integer',   // Template ID
    //         'message_body' => 'required|string',   // Message content
    //         'selected_users' => 'required|array',  // ✅ Expecting an array like [1, 2]
    //     ]);

    //     $type = $validated['type'];
    //     $templateId = $validated['template_id'];
    //     $message = $validated['message_body'];
    //     $users = $validated['selected_users']; // already array, no need json_decode

    //     if (empty($users)) {
    //         return response()->json(['success' => false, 'message' => 'No users selected.'], 400);
    //     }

    //     try {
    //         // 🔹 Fetch all FCM tokens for selected users in one query (optimized)
    //         $tokens = UserToken::whereIn('user_id', $users)
    //             ->whereNotNull('fcm_token')
    //             ->pluck('fcm_token', 'user_id')
    //             ->toArray();

    //         foreach ($users as $userId) {
    //             $user = SortkarSPUser::find($userId);

    //             if (!$user) {
    //                 Log::warning("User not found: ID {$userId}");
    //                 continue;
    //             }

    //             // Skip if user doesn’t have a valid FCM token
    //             if (empty($tokens[$userId])) {
    //                 Log::info("Skipping user ID {$userId} — no valid FCM token.");
    //                 continue;
    //             }

    //             // ✅ Prepare payload
    //             $data = [
    //                 'to' => $tokens[$userId], // FCM token
    //                 'user_id' => $user->id,
    //                 'name' => trim($user->first_name . ' ' . $user->last_name),
    //                 'email' => $user->email,
    //                 'phone' => $user->mobile1_number,
    //                 'message' => $message,
    //                 'template_id' => $templateId,
    //             ];

    //             // ✅ Unified helper call
    //             NotificationHelper::sendNotification($type, $data);
    //         }

    //         return response()->json(['success' => true, 'message' => 'Notifications sent successfully!']);
    //     } catch (\Exception $e) {
    //         Log::error('Error sending notifications: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong while sending notifications.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function sendNotification(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'template_id' => 'nullable|integer',
            'message_body' => 'required|string',
            'selected_users' => 'required|json',
            'is_scheduled' => 'boolean',
            'scheduled_at' => 'nullable|date',
        ]);

        $selectedUsers = json_decode($validated['selected_users'], true);

        $notification = NotificationPanel::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'template_id' => $validated['template_id'] ?? null,
            'message_body' => $validated['message_body'],
            'selected_users' => json_encode($selectedUsers), // ✅ FIXED: store as JSON
            'is_scheduled' => $validated['is_scheduled'] ?? false,
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'status' => 'queued',
        ]);


        // ✅ Always dispatch the job
        if ($notification->is_scheduled && $notification->scheduled_at) {
            ProcessNotificationJob::dispatch($notification)->delay($notification->scheduled_at);
        } else {
            ProcessNotificationJob::dispatch($notification);
        }

        return response()->json([
            'message' => $notification->is_scheduled
                ? 'Notification scheduled successfully.'
                : 'Notification queued for sending.',
        ]);
    }


}
