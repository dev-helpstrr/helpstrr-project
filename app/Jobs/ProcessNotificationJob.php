<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Helpers\NotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;
    public $userIdsChunk;

    public function __construct($notification, $userIdsChunk = [])
    {
        $this->notification = $notification;
        $this->userIdsChunk = $userIdsChunk;
    }

    public function handle()
    {
        dd('test');
        exit;
        Log::info('🟢 Job started for notification ID: ' . $this->notification->id);
        Log::info('🔹 Chunk: ' . json_encode($this->userIdsChunk));

        foreach ($this->userIdsChunk as $userId) {
            try {
                $user = \App\Models\SP\Auth\SortkarSPUser::find($userId);
                if (!$user) {
                    Log::warning("⚠️ User not found: ID {$userId}");
                    continue;
                }

                $log = NotificationLog::create([
                    'scheduled_notification_id' => $this->notification->id,
                    'user_id' => $userId,
                    'status' => 'processing',
                ]);

                $response = NotificationHelper::sendNotification(
                    $this->notification->type,
                    [
                        'user_id' => $user->id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                        'phone' => $user->mobile1_number,
                        'message' => $this->notification->message_body,
                        'template_id' => $this->notification->template_id,
                        'title' => $this->notification->title,
                    ]
                );

                $log->update([
                    'status' => 'sent',
                    'response' => is_string($response) ? $response : json_encode($response),
                    'sent_at' => now(),
                ]);

                Log::info("✅ Notification sent to user {$userId}");
            } catch (\Exception $e) {
                Log::error("❌ Failed to send to user {$userId}: " . $e->getMessage());

                NotificationLog::create([
                    'scheduled_notification_id' => $this->notification->id,
                    'user_id' => $userId,
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ]);
            }
        }

        Log::info('✅ Completed notification ID: ' . $this->notification->id);
    }

    public static function dispatchInChunks($notification)
    {
        $users = is_array($notification->selected_users)
            ? $notification->selected_users
            : json_decode($notification->selected_users, true);

        if (empty($users)) {
            Log::warning("⚠️ No users found for notification ID: {$notification->id}");
            return;
        }

        $chunks = array_chunk($users, 500);

        foreach ($chunks as $chunk) {
            self::dispatch($notification, $chunk);
        }

        Log::info("🧩 Dispatched " . count($chunks) . " jobs for notification ID: {$notification->id}");
    }
}
