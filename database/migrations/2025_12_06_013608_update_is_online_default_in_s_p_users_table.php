<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIsOnlineDefaultInSPUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $table->boolean('is_online')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $table->boolean('is_online')->default(1)->change(); // Revert to previous default if needed
        });
    }
}
