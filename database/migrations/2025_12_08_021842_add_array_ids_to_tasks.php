<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Arrays of category / subcategory / service IDs for task
            if (!Schema::hasColumn('tasks', 'category_ids')) {
                $table->string('category_ids', 255)
                    ->nullable()
                    ->after('category_id');
            }

            if (!Schema::hasColumn('tasks', 'subcategory_ids')) {
                $table->string('subcategory_ids', 255)
                    ->nullable()
                    ->after('subcategory_id');
            }

            if (!Schema::hasColumn('tasks', 'service_ids')) {
                $table->string('service_ids', 255)
                    ->nullable()
                    ->after('subcategory_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'service_ids')) {
                $table->dropColumn('service_ids');
            }
            if (Schema::hasColumn('tasks', 'subcategory_ids')) {
                $table->dropColumn('subcategory_ids');
            }
            if (Schema::hasColumn('tasks', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
        });
    }
};
