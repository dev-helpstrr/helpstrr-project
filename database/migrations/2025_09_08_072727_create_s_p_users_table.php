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
        if (!Schema::hasTable('s_p_users')) {
            Schema::create('s_p_users', function (Blueprint $table) {
                $table->id('id');
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('mobile1_number', 20)->nullable();
                $table->string('whatsapp', 20)->nullable();
                $table->string('dob', 100)->nullable();
                $table->string('gender', 100)->nullable();
                $table->string('profile_status', 200)->nullable();
                $table->string('avatar_url', 200)->nullable();
                $table->string('intrested_role', 200)->nullable();
                $table->string('prior_experience', 200)->nullable();
                $table->string('password', 255)->nullable();
                $table->string('confirm_password_hash', 255)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->string('latitude', 20)->nullable();
                $table->string('longitude', 20)->nullable();
                $table->string('coverage_radius', 200)->nullable();
                $table->string('has_two_wheeler', 30)->nullable();
                $table->string('profile_picture', 255)->nullable();
                $table->integer('experience_years')->nullable();
                $table->text('bio')->nullable();
                $table->string('id_proof', 255)->nullable();
                $table->enum('background_check_status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->decimal('avg_rating', 3, 2)->default(0.00);
                $table->integer('total_ratings')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->string('token', 300)->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('login_otp', 10)->nullable();
                $table->integer('is_opt_in')->nullable();
                $table->string('zoho_pushed_status', 30)->nullable();
                $table->timestamp('last_login')->nullable();
                $table->timestamps(); // created_at and updated_at
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_p_users');
    }
};
