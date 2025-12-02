<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_categories', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('slug')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->string('icon')->nullable()->change();
            $table->string('color')->nullable()->change();
            $table->boolean('is_active')->nullable()->change();
            $table->integer('sort_order')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('new_categories', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->string('icon')->nullable(false)->change();
            $table->string('color')->nullable(false)->change();
            $table->boolean('is_active')->nullable(false)->change();
            $table->integer('sort_order')->nullable(false)->change();
        });
    }
};
