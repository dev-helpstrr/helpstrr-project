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
        if (!Schema::hasTable('service_providers')) {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sp_user_id')->constrained('s_p_users')->onDelete('cascade');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->decimal('acceptance_rate', 5, 2)->default(0);
            $table->decimal('punctuality_score', 5, 2)->default(0);
            $table->decimal('behaviour_score', 5, 2)->default(0);
            $table->boolean('is_gold_level')->default(false);
            $table->decimal('cancellation_score', 5, 2)->default(0);
            $table->decimal('complaint_score', 5, 2)->default(0);
            $table->integer('rejection_frequency')->default(0);
            $table->timestamp('cooldown_until')->nullable();
            $table->timestamp('last_assigned_at')->nullable();
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_cancelled')->default(0);
            $table->integer('tasks_rejected')->default(0);
            $table->boolean('kyc_verified')->default(false);
            $table->enum('kyc_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('kyc_rejection_reason')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
