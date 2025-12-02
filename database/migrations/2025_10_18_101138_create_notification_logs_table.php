<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications_logs')) {

            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('notification_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('user_type', ['sp_user', 'customer', 'internal_user'])->default('sp_user');
                $table->enum('type', ['email', 'sms', 'whatsapp', 'in_app']);
                $table->text('message');
                $table->enum('status', ['pending', 'sent', 'failed']);
                $table->timestamp('attempted_at')->useCurrent();
                $table->text('response')->nullable();
                $table->json('meta')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
