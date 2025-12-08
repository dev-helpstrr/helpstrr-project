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
        Schema::table('sp_capabilities', function (Blueprint $table) {
            // Drop old foreign keys pointing to legacy tables
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
        });

        Schema::table('sp_capabilities', function (Blueprint $table) {
            // Recreate foreign keys pointing to the new hierarchy tables
            $table->foreign('category_id')
                ->references('id')->on('new_categories')
                ->onDelete('cascade');

            $table->foreign('subcategory_id')
                ->references('id')->on('new_subcategories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
        });

        Schema::table('sp_capabilities', function (Blueprint $table) {
            // Restore original foreign keys back to legacy tables
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');

            $table->foreign('subcategory_id')
                ->references('id')->on('subcategories')
                ->onDelete('cascade');
        });
    }
};
