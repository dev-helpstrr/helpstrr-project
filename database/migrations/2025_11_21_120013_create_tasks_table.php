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
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_address_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_provider_id')->nullable()->constrained()->onDelete('set null');
            
            // Task Details
            $table->integer('pax_count')->default(1);
            $table->integer('requested_hours');
            $table->integer('billable_hours'); // max(requested_hours, min_hours_for_subcategory)
            $table->json('dates'); // Array of dates for recurrence
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('recurrence_type', ['one_time', 'two_days', 'three_days'])->default('one_time');
            
            // Dietary Preference (single select)
            $table->foreignId('dietary_preference_id')->nullable()->constrained()->onDelete('set null');
            
            // Task Status
            $table->enum('status', [
                'requested', 'searching', 'assigned', 'on_the_way', 'arrived', 
                'otp_start_verified', 'started', 'paused', 'resumed', 'completed', 'rated'
            ])->default('requested');
            
            // OTP Management
            $table->string('start_otp', 6)->nullable();
            $table->string('end_otp', 6)->nullable();
            $table->timestamp('otp_start_verified_at')->nullable();
            $table->timestamp('otp_end_verified_at')->nullable();
            
            // Timing
            $table->timestamp('scheduled_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Pricing
            $table->decimal('total_amount', 10, 2);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            
            // Additional Info
            $table->text('special_instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['customer', 'service_provider', 'admin'])->nullable();
            
            // Rating
            $table->integer('customer_rating')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->integer('sp_rating')->nullable();
            $table->text('sp_feedback')->nullable();
            
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
        Schema::dropIfExists('tasks');
    }
};