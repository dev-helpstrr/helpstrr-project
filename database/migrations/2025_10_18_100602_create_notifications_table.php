<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {

            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('user_type', ['sp_user', 'customer', 'internal_user'])->default('sp_user');
                $table->enum('type', ['email', 'sms', 'whatsapp', 'in_app'])->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->text('message')->nullable();
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
