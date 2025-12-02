<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Change send_message from boolean → text, nullable, default null
            $table->text('send_message')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Revert back to boolean if needed
            $table->boolean('send_message')->default(false)->change();
        });
    }
};
