<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loc_countries')) {
            Schema::create('loc_countries', function (Blueprint $table) {
                $table->id();
                $table->string('zoho_country_id')->nullable();
                $table->string('name');
                $table->string('iso3', 3)->nullable();
                $table->string('numeric_code')->nullable();
                $table->string('iso2', 2)->nullable();
                $table->string('phonecode')->nullable();
                $table->string('capital')->nullable();
                $table->string('currency')->nullable();
                $table->string('currency_name')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('tld')->nullable();
                $table->string('native')->nullable();
                $table->string('region')->nullable();
                $table->string('subregion')->nullable();
                $table->longText('timezones')->nullable();
                $table->longText('translations')->nullable();
                $table->decimal('latitude', 10, 6)->nullable();
                $table->decimal('longitude', 10, 6)->nullable();
                $table->string('emoji', 10)->nullable();
                $table->string('emojiU')->nullable();
                $table->boolean('flag')->default(1);
                $table->string('wikiDataId')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('loc_countries')) {
            Schema::dropIfExists('loc_countries');
        }
    }
};
