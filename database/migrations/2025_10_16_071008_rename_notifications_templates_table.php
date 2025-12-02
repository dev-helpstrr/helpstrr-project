<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('notifications_templates', 'notification_templates');
    }

    public function down(): void
    {
        Schema::rename('notification_templates', 'notifications_templates');
    }
};
