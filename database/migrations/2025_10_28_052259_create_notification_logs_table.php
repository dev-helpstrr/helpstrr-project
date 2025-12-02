<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('notification_logs')) {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_notification_id')->constrained()->onDelete('cascade');
            $table->enum('user_type', ['sp_user', 'customer', 'internal_user'])->default('sp_user');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }
}

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};
