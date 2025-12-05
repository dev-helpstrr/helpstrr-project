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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->decimal('base_price', 8, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->integer('min_hours')->default(1);
            $table->integer('max_hours')->nullable();
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->decimal('travel_allowance', 8, 2)->default(0);
            $table->boolean('pax_required')->default(false);
            $table->integer('min_pax')->default(1);
            $table->integer('max_pax')->nullable();
            $table->boolean('recurrence_allowed')->default(true);
            $table->boolean('is_event_service')->default(false);
            $table->boolean('is_takeaway')->default(false);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('requirements')->nullable(); // Service specific requirements
            $table->json('features')->nullable(); // Service features
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};