<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('custom_notification_updates');
        Schema::dropIfExists('custom_notification_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: recreate if needed
        Schema::create('custom_notification_updates', function ($table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('custom_notification_logs', function ($table) {
            $table->id();
            $table->timestamps();
        });
    }
};
