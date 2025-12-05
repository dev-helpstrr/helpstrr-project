<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
            $table->string('action_type'); // broadcast_sent, accepted, rejected, timeout, conflict_resolved, auto_assigned
            $table->timestamp('action_timestamp');
            $table->integer('response_time_seconds')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('priority_score', 8, 2)->nullable();
            $table->boolean('conflict_resolution_applied')->default(false);
            $table->integer('assignment_round')->default(1);
            $table->foreignId('broadcast_id')->nullable()->constrained('task_broadcasts')->onDelete('set null');
            $table->string('rejection_reason')->nullable();
            $table->boolean('auto_assigned')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['task_id', 'action_type']);
            $table->index(['service_provider_id', 'action_type']);
            $table->index(['action_timestamp']);
            $table->index(['assignment_round']);
            $table->index(['conflict_resolution_applied']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignment_logs');
    }
};