<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_categories')) {
            Schema::create('service_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('service_categories')->nullOnDelete();
                $table->string('name', 191);
                $table->string('slug', 191)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
        Schema::table('s_p_users', function (Blueprint $table) {
            if (!Schema::hasColumn('s_p_users', 'service_category_id')) {
                $table->foreignId('service_category_id')->nullable()->after('id')->constrained('service_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('s_p_users', 'age')) {
                $table->integer('age')->nullable()->after('dob');
            }
            if (!Schema::hasColumn('s_p_users', 'alternate_mobile')) {
                $table->string('alternate_mobile', 30)->nullable()->after('mobile1_number');
            }
            if (!Schema::hasColumn('s_p_users', 'alternate_mobile_verified')) {
                $table->boolean('alternate_mobile_verified')->default(false)->after('alternate_mobile');
            }
            if (!Schema::hasColumn('s_p_users', 'mobile_verified')) {
                $table->boolean('mobile_verified')->default(false)->after('mobile1_number');
            }
            if (!Schema::hasColumn('s_p_users', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('s_p_users', 'can_work_weekends')) {
                $table->boolean('can_work_weekends')->default(false)->after('max_travel_distance');
            }
            if (!Schema::hasColumn('s_p_users', 'can_work_nights')) {
                $table->boolean('can_work_nights')->default(false)->after('can_work_weekends');
            }
            if (!Schema::hasColumn('s_p_users', 'expected_hourly_rate')) {
                $table->decimal('expected_hourly_rate', 10, 2)->nullable()->after('bio');
            }
            if (!Schema::hasColumn('s_p_users', 'expected_daily_rate')) {
                $table->decimal('expected_daily_rate', 10, 2)->nullable()->after('expected_hourly_rate');
            }
            if (!Schema::hasColumn('s_p_users', 'max_daily_working_hours')) {
                $table->integer('max_daily_working_hours')->nullable()->after('expected_daily_rate');
            }
            if (!Schema::hasColumn('s_p_users', 'max_travel_distance')) {
                $table->integer('max_travel_distance')->nullable()->after('max_daily_working_hours');
            }
            if (!Schema::hasColumn('s_p_users', 'special_conditions')) {
                $table->text('special_conditions')->nullable()->after('preferred_working_areas');
            }
            if (!Schema::hasColumn('s_p_users', 'additional_notes')) {
                $table->text('additional_notes')->nullable()->after('special_conditions');
            }
            $jsonCols = [
                'languages_known',
                'service_categories',
                'preferred_working_areas',
                'preferred_task_types',
                'certifications',
                'daily_availability',
                'weekly_off_days',
                'employer_references',
                'work_portfolio',
            ];
            foreach ($jsonCols as $col) {
                if (!Schema::hasColumn('s_p_users', $col)) {
                    $table->json($col)->nullable()->after('additional_notes');
                }
            }
        });
    }
    public function down(): void
    {
        Schema::table('s_p_users', function (Blueprint $table) {
            $drop = [
                'service_category_id','age','alternate_mobile','alternate_mobile_verified','mobile_verified','is_online',
                'can_work_weekends','can_work_nights','expected_hourly_rate','expected_daily_rate','max_daily_working_hours',
                'max_travel_distance','special_conditions','additional_notes','languages_known','service_categories',
                'preferred_working_areas','preferred_task_types','certifications','daily_availability','weekly_off_days',
                'employer_references','work_portfolio',
            ];
            foreach ($drop as $d) {
                if (Schema::hasColumn('s_p_users', $d)) {
                    try { $table->dropColumn($d); } catch (\Exception $e) { }
                }
            }
        });
        Schema::dropIfExists('service_categories');
    }
};
