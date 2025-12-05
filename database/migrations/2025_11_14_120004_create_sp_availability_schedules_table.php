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
        if (!Schema::hasTable('sp_availability_schedules')) {
        Schema::create('sp_availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sp_user_id')->constrained('s_p_users')->onDelete('cascade');

            // Day of week (0 = Sunday, 1 = Monday, etc.)
            $table->tinyInteger('day_of_week'); // 0-6

            // Time slots
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_available')->default(true);

            // Break times
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();

            // Special notes for the day
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['sp_user_id', 'day_of_week']);
            $table->unique(['sp_user_id', 'day_of_week']);
        });
    }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_availability_schedules');
    }
};
