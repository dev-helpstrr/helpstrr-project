<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIsActiveDefaultInServiceProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->boolean('is_active')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->change(); // Revert to previous default if needed
        });
    }
}
