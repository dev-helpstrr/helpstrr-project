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
        if (!Schema::hasTable('task_price_components')) {
            Schema::create('task_price_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            
            // Base pricing
            $table->decimal('base_amount', 10, 2);
            $table->decimal('hourly_rate', 8, 2);
            $table->integer('billable_hours');
            
            // Modifiers
            $table->decimal('night_adjustment', 10, 2)->default(0);
            $table->decimal('festive_surge_percentage', 5, 2)->default(0);
            $table->decimal('festive_adjustment', 10, 2)->default(0);
            $table->decimal('weather_surge_percentage', 5, 2)->default(0);
            $table->decimal('weather_adjustment', 10, 2)->default(0);
            $table->decimal('premium_sp_percentage', 5, 2)->default(0);
            $table->decimal('premium_sp_adjustment', 10, 2)->default(0);
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->decimal('stay_over_fee', 8, 2)->default(0);
            $table->decimal('subscription_discount', 10, 2)->default(0);
            
            // Final amounts
            $table->decimal('total_excl_gst', 10, 2);
            $table->decimal('gst_percentage', 5, 2)->default(18);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_incl_gst', 10, 2);
            
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_price_components');
    }
};