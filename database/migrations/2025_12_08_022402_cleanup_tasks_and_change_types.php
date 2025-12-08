<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 1) DROP the extra array columns if they exist
            if (Schema::hasColumn('tasks', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
            if (Schema::hasColumn('tasks', 'subcategory_ids')) {
                $table->dropColumn('subcategory_ids');
            }
            if (Schema::hasColumn('tasks', 'service_ids')) {
                $table->dropColumn('service_ids');
            }

            // 2) DROP foreign keys on category/subcategory/service if they exist
            if (Schema::hasColumn('tasks', 'category_id')) {
                $table->dropForeign(['category_id']);
            }
            if (Schema::hasColumn('tasks', 'subcategory_id')) {
                $table->dropForeign(['subcategory_id']);
            }
            if (Schema::hasColumn('tasks', 'service_id')) {
                $table->dropForeign(['service_id']);
            }

            // 3) CHANGE them to VARCHAR for comma-separated ids
            if (Schema::hasColumn('tasks', 'category_id')) {
                $table->string('category_id', 255)->nullable()->change();
            }
            if (Schema::hasColumn('tasks', 'subcategory_id')) {
                $table->string('subcategory_id', 255)->nullable()->change();
            }
            if (Schema::hasColumn('tasks', 'service_id')) {
                $table->string('service_id', 255)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Same as above, leave empty or write custom rollback if needed.
    }
};
