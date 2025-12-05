<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');
    }

    public function down(): void
    {
        // Optionally, you can recreate the old table structure here
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
