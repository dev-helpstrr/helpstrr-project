<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            // New columns to store arrays of IDs as comma-separated VARCHAR
            // Example values: "1,2,3"
            if (!Schema::hasColumn('sp_capabilities', 'category_ids')) {
                $table->string('category_ids', 255)
                    ->nullable()
                    ->after('service_provider_id');
            }

            if (!Schema::hasColumn('sp_capabilities', 'subcategory_ids')) {
                $table->string('subcategory_ids', 255)
                    ->nullable()
                    ->after('category_ids');
            }

            if (!Schema::hasColumn('sp_capabilities', 'service_ids')) {
                $table->string('service_ids', 255)
                    ->nullable()
                    ->after('subcategory_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            if (Schema::hasColumn('sp_capabilities', 'service_ids')) {
                $table->dropColumn('service_ids');
            }
            if (Schema::hasColumn('sp_capabilities', 'subcategory_ids')) {
                $table->dropColumn('subcategory_ids');
            }
            if (Schema::hasColumn('sp_capabilities', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
        });
    }
};
