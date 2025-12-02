<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loc_states')) {
            Schema::create('loc_states', function (Blueprint $table) {
                $table->id();
                $table->string('zoho_state_id')->nullable();
                $table->string('name');
                $table->unsignedBigInteger('country_id')->nullable();
                $table->string('country_code')->nullable();
                $table->string('fips_code')->nullable();
                $table->string('iso2')->nullable();
                $table->string('iso3166_2')->nullable();
                $table->string('type')->nullable();
                $table->integer('level')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('native')->nullable();
                $table->decimal('latitude', 10, 6)->nullable();
                $table->decimal('longitude', 10, 6)->nullable();
                $table->string('timezone')->nullable();
                $table->boolean('flag')->default(1);
                $table->string('wikiDataId')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loc_states')) {
            Schema::dropIfExists('loc_states');
        }
    }
};
