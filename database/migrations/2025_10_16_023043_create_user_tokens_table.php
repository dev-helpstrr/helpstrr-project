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
        if (!Schema::hasTable('user_tokens')) {
            Schema::create('user_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('fcm_token', 350)->nullable();
                $table->string('device_id', 200)->nullable();
                $table->integer('user_id')->nullable();
                $table->string('user_type', 80)->nullable();
                $table->timestamp('installed_on')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
    }
};
