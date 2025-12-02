<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('referrals')) {
        Schema::create('referrals', function (Blueprint $table) {
            $table->bigIncrements('id');

            // the user who owns this referral code (the referrer)
            $table->unsignedBigInteger('referral_by')->nullable()->index();

            // when this referral was used to refer someone (can be null until used)
            $table->unsignedBigInteger('referral_to')->nullable()->index();
            $table->string('referral_to_phone', 30)->nullable();
            $table->string('referral_to_name', 200)->nullable();
            $table->string('referral_to_job_role', 150)->nullable();

            // optional counters

            $table->timestamp('referred_on')->nullable(); // when referral_to was converted to a user
            $table->timestamps();

            // FK constraints if you want (optional)
            // $table->foreign('referral_by')->references('id')->on('s_p_users')->onDelete('set null');
            // $table->foreign('referral_to')->references('id')->on('s_p_users')->onDelete('set null');
        });
    }
}

    public function down()
    {
        Schema::dropIfExists('referrals');
    }
}
