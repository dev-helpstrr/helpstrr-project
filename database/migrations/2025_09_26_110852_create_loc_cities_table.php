<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loc_cities')) {
            Schema::create('loc_cities', function (Blueprint $table) {
                $table->id();
                $table->string('zoho_city_id')->nullable();
                $table->string('name');
                $table->unsignedBigInteger('state_id')->nullable();
                $table->string('state_code')->nullable();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->string('country_code')->nullable();
                $table->decimal('latitude', 10, 5)->nullable();
                $table->decimal('longitude', 10, 5)->nullable();
                $table->string('timezone')->nullable();
                $table->boolean('flag')->default(1);
                $table->string('wikiDataId')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loc_cities')) {
            Schema::dropIfExists('loc_cities');
        }
    }
};
