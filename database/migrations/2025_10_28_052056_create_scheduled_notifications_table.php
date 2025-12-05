<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('scheduled_notifications')) {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // subject/title
            $table->enum('user_type', ['sp_user', 'customer', 'internal_user'])->default('sp_user');
            $table->string('type'); // email, sms, whatsapp, in-app
            $table->unsignedBigInteger('template_id')->nullable();
            $table->longText('message_body')->nullable();
            $table->json('selected_users');
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }
}

    public function down()
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
