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
        if (Schema::hasTable('sp_performance_metrics')) {
            Schema::table('sp_performance_metrics', function (Blueprint $table) {
                // Add missing columns that the resource expects
                if (!Schema::hasColumn('sp_performance_metrics', 'total_tasks')) {
                    $table->integer('total_tasks')->default(0)->after('service_provider_id');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'completed_tasks')) {
                    $table->integer('completed_tasks')->default(0)->after('total_tasks');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'cancelled_tasks')) {
                    $table->integer('cancelled_tasks')->default(0)->after('completed_tasks');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'quality_score')) {
                    $table->decimal('quality_score', 5, 2)->default(0)->after('punctuality_score');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'response_time_avg')) {
                    $table->integer('response_time_avg')->default(0)->after('quality_score'); // in minutes
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'complaints_count')) {
                    $table->integer('complaints_count')->default(0)->after('response_time_avg');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'badges')) {
                    $table->json('badges')->nullable()->after('complaints_count');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'incentives')) {
                    $table->json('incentives')->nullable()->after('badges');
                }
                if (!Schema::hasColumn('sp_performance_metrics', 'last_updated')) {
                    $table->timestamp('last_updated')->nullable()->after('incentives');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sp_performance_metrics')) {
            Schema::table('sp_performance_metrics', function (Blueprint $table) {
                $columnsToRemove = [
                    'total_tasks', 'completed_tasks', 'cancelled_tasks', 
                    'quality_score', 'response_time_avg', 'complaints_count',
                    'badges', 'incentives', 'last_updated'
                ];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('sp_performance_metrics', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
