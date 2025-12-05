<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Change type from enum to string
            $table->string('type')->nullable()->change();
            $table->string('message_type')->nullable()->change();


            // Change template_status from enum to string
            $table->string('template_status')->default('inactive')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Revert back to enum in case of rollback
            $table->enum('type', ['email', 'whatsapp', 'sms', 'in_app'])->change();
            $table->enum('template_status', ['active', 'inactive'])->default('inactive')->change();
            $table->enum('message_type', ['TEXT', 'MEDIA'])->nullable()->change();

        });
    }
};
