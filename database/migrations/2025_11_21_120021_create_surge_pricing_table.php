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
        if (!Schema::hasTable('surge_pricing')) {
            Schema::create('surge_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['festive', 'weather', 'demand']);
            $table->decimal('percentage', 5, 2); // Surge percentage
            $table->text('description')->nullable();
            
            // Applicability
            $table->json('applicable_categories')->nullable(); // Array of category IDs
            $table->json('applicable_cities')->nullable(); // Array of city names
            
            // Timing
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            
            // Conditions
            $table->json('conditions')->nullable(); // Weather conditions, demand thresholds, etc.
            
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
        Schema::dropIfExists('surge_pricing');
    }
};