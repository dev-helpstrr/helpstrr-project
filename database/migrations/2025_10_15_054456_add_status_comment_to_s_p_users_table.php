<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $table->tinyInteger('is_verified')
                ->default(0)
                ->comment('0 - Pending, 1 - Verified, 2 - Rejected')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $table->tinyInteger('is_verified')->default(0)->comment(null)->change();
        });
    }
};
