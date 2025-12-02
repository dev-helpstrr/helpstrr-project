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
        Schema::create('subcategory_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('new_subcategories')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Mark primary subcategory for service
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['subcategory_id', 'service_id']);
            $table->index(['subcategory_id', 'is_primary']);
            $table->index(['service_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategory_service');
    }
};