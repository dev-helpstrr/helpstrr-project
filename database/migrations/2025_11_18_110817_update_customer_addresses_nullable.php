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
        Schema::table('customer_addresses', function (Blueprint $table) {

            if (Schema::hasColumn('customer_addresses', 'type')) {
                $table->string('type')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'address_line1')) {
                $table->string('address_line1')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'address_line2')) {
                $table->string('address_line2')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'city')) {
                $table->string('city')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'state')) {
                $table->string('state')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'zip_code')) {
                $table->string('zip_code')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'country')) {
                $table->string('country')->default('India')->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Optional rollback
    }
};
