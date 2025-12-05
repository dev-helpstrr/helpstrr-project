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
        if (!Schema::hasTable('emergency_alerts')) {
            Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['customer', 'service_provider']);
            $table->unsignedBigInteger('user_id');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->enum('alert_type', ['panic_button', 'sos', 'safety_concern', 'emergency']);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_address')->nullable();
            $table->enum('status', ['active', 'resolved', 'false_alarm'])->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional alert info
            $table->timestamps();
            
            $table->index(['user_type', 'user_id']);
            $table->index('status');
            $table->index('created_at');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};
