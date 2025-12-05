<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            // Rename existing columns
            if (Schema::hasColumn('customer_addresses', 'address_line1')) {
                $table->renameColumn('address_line1', 'address_line_1');
            }

            if (Schema::hasColumn('customer_addresses', 'address_line2')) {
                $table->renameColumn('address_line2', 'address_line_2');
            }

            if (Schema::hasColumn('customer_addresses', 'zip_code')) {
                $table->renameColumn('zip_code', 'pincode');
            }

            // Add new columns
            if (!Schema::hasColumn('customer_addresses', 'landmark')) {
                $table->text('landmark')->nullable()->after('address_line_2');
            }

            if (!Schema::hasColumn('customer_addresses', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('landmark');
            }

            if (!Schema::hasColumn('customer_addresses', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            // Reverse the renamed columns
            if (Schema::hasColumn('customer_addresses', 'address_line_1')) {
                $table->renameColumn('address_line_1', 'address_line1');
            }

            if (Schema::hasColumn('customer_addresses', 'address_line_2')) {
                $table->renameColumn('address_line_2', 'address_line2');
            }

            if (Schema::hasColumn('customer_addresses', 'pincode')) {
                $table->renameColumn('pincode', 'zip_code');
            }

            // Drop added columns
            $table->dropColumn(['landmark', 'is_default', 'is_active']);
        });
    }
};
