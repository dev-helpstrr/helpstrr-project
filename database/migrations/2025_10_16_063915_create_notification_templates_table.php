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
        if (!Schema::hasTable('notifications_templates')) {
            Schema::create('notifications_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title'); // Template Title
                $table->enum('type', ['email', 'whatsapp', 'sms', 'in_app']); // Template type
                $table->string('email_subject')->nullable(); // Only for Email
                $table->text('body'); // Template Body (common)
                $table->boolean('is_template')->default(false); // Only for WhatsApp/SMS
                $table->enum('message_type', ['TEXT', 'MEDIA'])->nullable(); // WhatsApp only
                $table->boolean('send_message')->default(false); // WhatsApp only
                $table->string('footer')->nullable(); // Footer for WhatsApp/SMS
                $table->string('url')->nullable(); // API URL for WhatsApp/SMS
                $table->string('userid')->nullable(); // API UserID
                $table->string('password')->nullable(); // API Password
                $table->string('v')->nullable(); // API Version
                $table->string('format')->nullable(); // API Format
                $table->string('msg_type')->nullable(); // API msg_type
                $table->enum('template_status', ['active', 'inactive'])->default('inactive'); // Template Status
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
