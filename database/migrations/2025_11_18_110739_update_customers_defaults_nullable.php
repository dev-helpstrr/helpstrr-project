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
        Schema::table('customers', function (Blueprint $table) {
            // Make all optional fields nullable
            if (Schema::hasColumn('customers', 'full_name')) {
                $table->string('full_name')->nullable()->change();
            }

            if (Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable()->change();
            }

            if (Schema::hasColumn('customers', 'profile_photo_url')) {
                $table->string('profile_photo_url')->nullable()->change();
            }

            if (Schema::hasColumn('customers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->change();
            }

            if (Schema::hasColumn('customers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->change();
            }

            if (Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')->default(1)->change();
            }

            if (Schema::hasColumn('customers', 'mobile_verified')) {
                $table->boolean('mobile_verified')->default(0)->change();
            }

            if (Schema::hasColumn('customers', 'login_otp')) {
                $table->string('login_otp', 10)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // No need to revert (optional)
    }
};
