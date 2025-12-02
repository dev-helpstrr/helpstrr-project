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
        if (!Schema::hasTable('issues')) {
            Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_provider_id')->nullable()->constrained()->onDelete('set null');
            
            // Issue Details
            $table->enum('reported_by', ['customer', 'service_provider', 'admin']);
            $table->enum('issue_type', [
                'quality_issue', 'behaviour_issue', 'timing_issue', 'payment_issue', 
                'cancellation_dispute', 'rating_dispute', 'other'
            ]);
            $table->string('title');
            $table->text('description');
            $table->json('attachments')->nullable(); // Array of file URLs
            
            // Resolution
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            // Compensation
            $table->decimal('compensation_amount', 10, 2)->default(0);
            $table->enum('compensation_type', ['none', 'refund', 'credit', 'discount'])->default('none');
            $table->text('compensation_notes')->nullable();
            
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
        Schema::dropIfExists('issues');
    }
};