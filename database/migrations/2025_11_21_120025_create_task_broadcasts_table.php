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
        if (!Schema::hasTable('task_broadcasts')) {
            Schema::create('task_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade');
            
            // Broadcast Details
            $table->enum('broadcast_round', ['A', 'B', 'new_sp_boost']); // A = top 5, B = next 15, new_sp_boost = random 5 new SPs
            $table->integer('distance_km'); // Distance from task location
            $table->decimal('sp_rating', 3, 2)->nullable();
            $table->integer('sp_rank_in_round'); // Ranking within the broadcast round
            
            // Response
            $table->enum('response', ['pending', 'accepted', 'rejected', 'timeout'])->default('pending');
            $table->timestamp('sent_at');
            $table->timestamp('responded_at')->nullable();
            $table->integer('response_time_seconds')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Timeout Management
            $table->integer('timeout_seconds')->default(60); // 60 seconds for response
            $table->timestamp('expires_at');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['task_id', 'broadcast_round']);
            $table->index(['service_provider_id', 'sent_at']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_broadcasts');
    }
};