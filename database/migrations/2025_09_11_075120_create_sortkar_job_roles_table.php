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
        if (!Schema::hasTable('sortkar_job_roles')) {
        Schema::create('sortkar_job_roles', function (Blueprint $table) {
            $table->id();
            $table->string('zoho_job_role_id')->unique(); // from Zoho response
            $table->string('role_name');                  // job role name
            $table->enum('role_status', ['active', 'inactive'])->default('active');
            $table->timestamps(); // created_at, updated_at
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sortkar_job_roles');
    }
};
