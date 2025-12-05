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
        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                // Add missing columns that the resource expects
                if (!Schema::hasColumn('issues', 'reporter_type')) {
                    $table->enum('reporter_type', ['customer', 'service_provider', 'admin'])->after('service_provider_id');
                }
                if (!Schema::hasColumn('issues', 'reporter_id')) {
                    $table->unsignedBigInteger('reporter_id')->after('reporter_type');
                }
                if (!Schema::hasColumn('issues', 'category')) {
                    $table->enum('category', ['service_quality', 'payment', 'behavior', 'safety', 'technical', 'refund', 'other'])->after('issue_type');
                }
                if (!Schema::hasColumn('issues', 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->nullable()->after('compensation_amount');
                }
                if (!Schema::hasColumn('issues', 'refund_status')) {
                    $table->enum('refund_status', ['pending', 'approved', 'rejected', 'processed'])->nullable()->after('refund_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                $columnsToRemove = ['reporter_type', 'reporter_id', 'category', 'refund_amount', 'refund_status'];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('issues', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
