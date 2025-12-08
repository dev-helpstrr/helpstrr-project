<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            $table->dropForeign('sp_capabilities_service_provider_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('sp_capabilities', function (Blueprint $table) {
            $table->foreign('service_provider_id')
                ->references('id')->on('service_providers')
                ->onDelete('cascade');
        });
    }
};
