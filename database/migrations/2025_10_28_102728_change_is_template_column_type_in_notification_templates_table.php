<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Change column type from tinyint/boolean to integer
            $table->integer('is_template')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            // Revert back to boolean if rolled back
            $table->boolean('is_template')->default(false)->change();
        });
    }
};
