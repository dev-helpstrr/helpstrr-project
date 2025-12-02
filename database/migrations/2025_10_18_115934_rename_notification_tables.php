<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::rename('notifications', 'custom_notification_updates');
        }

        if (Schema::hasTable('notification_logs')) {
            Schema::rename('notification_logs', 'custom_notification_logs');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('custom_notification_updates')) {
            Schema::rename('custom_notification_updates', 'notification_updates');
        }

        if (Schema::hasTable('custom_notification_logs')) {
            Schema::rename('custom_notification_logs', 'notification_logs');
        }
    }
};
