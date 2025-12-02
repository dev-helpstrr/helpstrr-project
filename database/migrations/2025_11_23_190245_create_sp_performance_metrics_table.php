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
        if (!Schema::hasTable('sp_performance_metrics')) {
            Schema::create('sp_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('tasks_offered')->default(0);
            $table->integer('tasks_accepted')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_cancelled')->default(0);
            $table->decimal('acceptance_rate', 5, 2)->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->integer('punctuality_score')->default(0); // 0-100
            $table->integer('complaints_received')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->integer('online_hours')->default(0); // in minutes
            $table->enum('badge_level', ['bronze', 'silver', 'gold', 'platinum'])->nullable();
            $table->timestamps();
            
            $table->unique(['service_provider_id', 'metric_date']);
            $table->index('metric_date');
            $table->index('badge_level');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_performance_metrics');
    }
};
