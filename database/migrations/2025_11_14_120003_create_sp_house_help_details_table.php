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
        if (!Schema::hasTable('sp_house_help_details')) {
        Schema::create('sp_house_help_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sp_user_id')->constrained('s_p_users')->onDelete('cascade');

            // Skills (Multi-select capabilities)
            $table->boolean('can_clean_utensils')->default(false);
            $table->boolean('can_sweep_mop')->default(false);
            $table->boolean('can_dust')->default(false);
            $table->boolean('can_wash_clothes')->default(false);
            $table->boolean('can_iron_clothes')->default(false);
            $table->boolean('can_clean_bathroom')->default(false);
            $table->boolean('can_clean_kitchen')->default(false);
            $table->boolean('can_deep_clean')->default(false);

            // Additional Capabilities
            $table->boolean('comfortable_with_pets')->default(false);
            $table->integer('max_hours_per_task')->nullable();
            $table->boolean('can_assist_elderly')->default(false);
            $table->boolean('can_handle_babies')->default(false);

            // Specialized Services
            $table->boolean('can_cook_basic_meals')->default(false);
            $table->boolean('can_grocery_shopping')->default(false);
            $table->boolean('can_gardening')->default(false);
            $table->boolean('can_car_cleaning')->default(false);

            // Work Preferences
            $table->json('preferred_work_timings')->nullable(); // Array of time slots
            $table->boolean('available_weekends')->default(true);
            $table->boolean('available_festivals')->default(false);
            $table->integer('min_hours_per_day')->nullable();
            $table->integer('max_hours_per_day')->nullable();

            // Pricing
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('daily_rate', 8, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();

            // Equipment & Supplies
            $table->boolean('has_own_cleaning_supplies')->default(false);
            $table->boolean('has_own_equipment')->default(false);
            $table->json('available_equipment')->nullable(); // vacuum, mop, etc.

            $table->timestamps();

            $table->index('sp_user_id');
        });
    }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_house_help_details');
    }
};
