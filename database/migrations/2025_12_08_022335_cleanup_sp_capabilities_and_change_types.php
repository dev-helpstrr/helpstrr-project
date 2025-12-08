<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            // 1) DROP the extra array columns if they exist
            if (Schema::hasColumn('sp_capabilities', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
            if (Schema::hasColumn('sp_capabilities', 'subcategory_ids')) {
                $table->dropColumn('subcategory_ids');
            }
            if (Schema::hasColumn('sp_capabilities', 'service_ids')) {
                $table->dropColumn('service_ids');
            }

            // 2) DROP foreign keys on the columns we are about to change
            //    These names follow Laravel's default naming convention
            if (Schema::hasColumn('sp_capabilities', 'category_id')) {
                $table->dropForeign(['category_id']);
            }
            if (Schema::hasColumn('sp_capabilities', 'subcategory_id')) {
                $table->dropForeign(['subcategory_id']);
            }
            if (Schema::hasColumn('sp_capabilities', 'service_id')) {
                // Only if you had added service_id with FK earlier
                $table->dropForeign(['service_id']);
            }

            // 3) CHANGE column types to VARCHAR so you can store "1,2,3"
            if (Schema::hasColumn('sp_capabilities', 'category_id')) {
                $table->string('category_id', 255)->nullable()->change();
            }
            if (Schema::hasColumn('sp_capabilities', 'subcategory_id')) {
                $table->string('subcategory_id', 255)->nullable()->change();
            }
            if (Schema::hasColumn('sp_capabilities', 'service_id')) {
                $table->string('service_id', 255)->nullable()->change();
            }

            // Optional: if your unique index depends on these, you can drop it
            // If this exists and you don't want it anymore:
            // $table->dropUnique('sp_subcategory_unique');
        });
    }

    public function down(): void
    {
        // You can leave this empty or add custom logic if you ever want to revert.
        // Reverting from VARCHAR back to BIGINT + FKs is destructive and needs manual handling.
    }
};
