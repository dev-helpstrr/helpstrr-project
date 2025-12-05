<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_notifications', function (Blueprint $table) {
            // Change status column to string (varchar)
            $table->string('status', 50)->default('queued')->change();
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_notifications', function (Blueprint $table) {
            // Rollback to enum type if needed
            $table->enum('status', ['queued', 'processing', 'sent', 'failed'])->default('queued')->change();
        });
    }
};
