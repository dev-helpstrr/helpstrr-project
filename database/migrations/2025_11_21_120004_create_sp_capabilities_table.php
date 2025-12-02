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
        if (!Schema::hasTable('sp_capabilities')) {
        Schema::create('sp_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->integer('max_pax_capacity')->default(1);
            $table->boolean('night_shift_available')->default(false);
            $table->integer('max_travel_distance_km')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_provider_id', 'subcategory_id'], 'sp_subcategory_unique');
        });
    }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_capabilities');
    }
};
