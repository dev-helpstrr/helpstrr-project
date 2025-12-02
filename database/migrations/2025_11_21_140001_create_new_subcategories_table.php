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
        Schema::create('new_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->integer('min_hours')->default(1);
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->boolean('pax_required')->default(false);
            $table->boolean('recurrence_allowed')->default(true);
            $table->boolean('is_event_category')->default(false);
            $table->boolean('is_takeaway')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
        Schema::dropIfExists('new_subcategories');
    }
};