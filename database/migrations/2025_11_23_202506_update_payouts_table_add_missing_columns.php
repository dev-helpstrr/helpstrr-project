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
        if (Schema::hasTable('payouts')) {
            Schema::table('payouts', function (Blueprint $table) {
                // Add missing columns that the resource expects
                if (!Schema::hasColumn('payouts', 'amount')) {
                    $table->decimal('amount', 10, 2)->after('task_id');
                }
                if (!Schema::hasColumn('payouts', 'platform_fee')) {
                    $table->decimal('platform_fee', 10, 2)->after('amount');
                }
                if (!Schema::hasColumn('payouts', 'payment_method')) {
                    $table->enum('payment_method', ['bank_transfer', 'upi', 'wallet'])->after('payout_method');
                }
                if (!Schema::hasColumn('payouts', 'notes')) {
                    $table->text('notes')->nullable()->after('failure_reason');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payouts')) {
            Schema::table('payouts', function (Blueprint $table) {
                $columnsToRemove = ['amount', 'platform_fee', 'payment_method', 'notes'];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('payouts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
