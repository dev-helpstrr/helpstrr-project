<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationPanel;
use App\Jobs\ProcessNotificationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessScheduledNotifications extends Command
{
    protected $signature = 'notifications:process-scheduled';
    protected $description = 'Process scheduled notifications that are due';

    public function handle()
    {
        $now = Carbon::now();

        $notifications = NotificationPanel::where('is_scheduled', '0')
            // ->where('status', 'queued')

            ->get();

        if ($notifications->isEmpty()) {
            Log::info('No scheduled notifications due at ' . $now);
            $this->info('No scheduled notifications due.');
            return;
        }

        foreach ($notifications as $notification) {
            Log::info("Dispatching job for notification ID: {$notification->id}");

            // ✅ Use chunked dispatch method
            \App\Jobs\ProcessNotificationJob::dispatchInChunks($notification);

            $notification->update(['status' => 'processing']);
        }

        $this->info("Processed {$notifications->count()} scheduled notifications.");
    }
}
