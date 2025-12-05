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
        if (!Schema::hasTable('sp_chef_details')) {
        Schema::create('sp_chef_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sp_user_id')->constrained('s_p_users')->onDelete('cascade');

            // Experience & Cooking Details
            $table->integer('years_of_cooking_experience')->nullable();
            $table->enum('chef_type', ['home_cook', 'event_cook', 'specialist_cook'])->nullable();

            // Cuisine Capabilities
            $table->json('cuisine_specialties')->nullable(); // ['bengali', 'north_indian', 'south_indian', 'chinese', 'continental', 'mughlai', 'snacks', 'biryani']

            // Pax Handling Capacity
            $table->json('pax_capacity')->nullable(); // ['1-4', '5-10', '10-25', '25-50', '50+']

            // Meal Type Expertise
            $table->boolean('can_cook_breakfast')->default(false);
            $table->boolean('can_cook_lunch')->default(false);
            $table->boolean('can_cook_dinner')->default(false);
            $table->boolean('can_cook_full_day')->default(false);
            $table->boolean('can_cook_special_occasions')->default(false);

            // Dietary Preferences
            $table->enum('dietary_preference', ['veg_only', 'veg_nonveg', 'jain_food', 'all'])->default('all');

            // Additional Capabilities
            $table->boolean('can_bring_utensils')->default(false);
            $table->boolean('can_bring_raw_materials')->default(false);
            $table->string('hygiene_certification')->nullable();

            // Pricing
            $table->decimal('price_per_meal', 8, 2)->nullable();
            $table->decimal('price_per_day', 8, 2)->nullable();
            $table->decimal('price_per_event', 10, 2)->nullable();

            // Portfolio
            $table->json('food_portfolio_images')->nullable();
            $table->text('speciality_description')->nullable();

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
        Schema::dropIfExists('sp_chef_details');
    }
};
