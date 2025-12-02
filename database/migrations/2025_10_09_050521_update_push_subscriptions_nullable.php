<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscribable_id')->nullable()->change();
            $table->string('subscribable_type')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscribable_id')->nullable(false)->change();
            $table->string('subscribable_type')->nullable(false)->change();
        });
    }
};
