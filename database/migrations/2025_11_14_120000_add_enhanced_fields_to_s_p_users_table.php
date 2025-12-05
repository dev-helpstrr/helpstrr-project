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
            // Basic Personal Details Enhancement
            $table->integer('age')->nullable()->after('dob');
            $table->string('alternate_mobile', 20)->nullable()->after('mobile1_number');
            $table->json('languages_known')->nullable()->after('gender');
            $table->boolean('mobile_verified')->default(false)->after('mobile1_number');
            $table->boolean('alternate_mobile_verified')->default(false)->after('alternate_mobile');
            
            // Category Selection
            $table->json('service_categories')->nullable()->after('intrested_role'); // ['house_help', 'driver', 'chef']
            
            // Availability Settings
            $table->json('daily_availability')->nullable(); // Time slots for each day
            $table->json('weekly_off_days')->nullable(); // Array of off days
            $table->boolean('is_online')->default(true);
            $table->integer('max_travel_distance')->nullable(); // in kilometers
            
            // Performance & Quality
            $table->json('employer_references')->nullable();
            $table->json('work_portfolio')->nullable(); // Array of image URLs
            $table->json('certifications')->nullable();
            
            // Preferences & Limitations
            $table->json('preferred_working_areas')->nullable();
            $table->json('preferred_task_types')->nullable();
            $table->integer('max_daily_working_hours')->nullable();
            $table->text('special_conditions')->nullable();
            
            // Enhanced profile fields
            $table->decimal('expected_hourly_rate', 8, 2)->nullable();
            $table->decimal('expected_daily_rate', 8, 2)->nullable();
            $table->boolean('can_work_weekends')->default(true);
            $table->boolean('can_work_nights')->default(false);
            $table->text('additional_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'alternate_mobile',
                'languages_known',
                'mobile_verified',
                'alternate_mobile_verified',
                'service_categories',
                'daily_availability',
                'weekly_off_days',
                'is_online',
                'max_travel_distance',
                'employer_references',
                'work_portfolio',
                'certifications',
                'preferred_working_areas',
                'preferred_task_types',
                'max_daily_working_hours',
                'special_conditions',
                'expected_hourly_rate',
                'expected_daily_rate',
                'can_work_weekends',
                'can_work_nights',
                'additional_notes'
            ]);
        });
    }
};