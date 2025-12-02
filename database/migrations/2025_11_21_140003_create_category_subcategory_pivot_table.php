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
        Schema::create('category_subcategory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('new_categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('new_subcategories')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Mark primary category for subcategory
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['category_id', 'subcategory_id']);
            $table->index(['category_id', 'is_primary']);
            $table->index(['subcategory_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_subcategory');
    }
};