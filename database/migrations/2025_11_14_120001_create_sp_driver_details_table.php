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
        if (!Schema::hasTable('sp_driver_details')) {
        Schema::create('sp_driver_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sp_user_id')->constrained('s_p_users')->onDelete('cascade');

            // Driving License Details
            $table->string('license_number')->nullable();
            $table->string('license_front_image')->nullable();
            $table->string('license_back_image')->nullable();
            $table->date('license_expiry_date')->nullable();

            // Driving Experience
            $table->integer('years_of_experience')->nullable();
            $table->boolean('city_driving_experience')->default(false);
            $table->boolean('highway_driving_experience')->default(false);
            $table->boolean('night_driving_experience')->default(false);
            $table->boolean('traffic_heavy_experience')->default(false);

            // Vehicle Related
            $table->json('transmission_types')->nullable(); // ['manual', 'automatic', 'both']
            $table->json('vehicle_segments')->nullable(); // ['hatchback', 'sedan', 'suv', 'mpv']

            // Service Capabilities
            $table->boolean('ready_for_outstation')->default(false);
            $table->boolean('ready_for_oneway_outstation')->default(false);
            $table->boolean('ready_for_roundtrip_outstation')->default(false);
            $table->boolean('ready_for_airport_pickup')->default(false);
            $table->boolean('ready_for_office_commute')->default(false);

            // Comfort Level
            $table->boolean('comfortable_long_hours')->default(false);
            $table->boolean('comfortable_luggage_handling')->default(false);
            $table->boolean('comfortable_waiting_time')->default(false);

            // Pricing
            $table->decimal('expected_hourly_rate', 8, 2)->nullable();
            $table->decimal('expected_daily_rate', 8, 2)->nullable();
            $table->decimal('outstation_per_km_rate', 8, 2)->nullable();

            $table->timestamps();

            $table->index('sp_user_id');
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_driver_details');
    }
};
