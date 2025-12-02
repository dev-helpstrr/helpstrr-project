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
        Schema::table('s_p_users', function (Blueprint $table) {
            if (!Schema::hasColumn('s_p_users', 'referral_id')) {
                $table->string('referral_id', 150)->nullable()->unique()->after('token');
            }

            if (!Schema::hasColumn('s_p_users', 'referral_url')) {
                $table->string('referral_url', 500)->nullable()->after('referral_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            if (Schema::hasColumn('s_p_users', 'referral_id')) {
                $table->dropColumn('referral_id');
            }

            if (Schema::hasColumn('s_p_users', 'referral_url')) {
                $table->dropColumn('referral_url');
            }
        });
    }
};
