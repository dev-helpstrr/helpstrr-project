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
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('monthly_price', 8, 2);
            $table->decimal('yearly_price', 8, 2)->nullable();
            
            // Discount Details
            $table->enum('discount_type', ['flat', 'percentage']);
            $table->decimal('discount_value', 8, 2);
            $table->decimal('max_discount_amount', 8, 2)->nullable(); // For percentage discounts
            
            // Limits
            $table->integer('max_bookings_per_month')->nullable();
            $table->json('applicable_categories')->nullable(); // Array of category IDs
            
            // Features
            $table->json('features')->nullable(); // Array of features included
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};