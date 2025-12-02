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
        if (!Schema::hasTable('sp_location_tracking')) {
            Schema::create('sp_location_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            
            // Location Data
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable(); // GPS accuracy in meters
            $table->decimal('speed', 8, 2)->nullable(); // Speed in km/h
            $table->integer('heading')->nullable(); // Direction in degrees
            
            // Status
            $table->enum('status', ['idle', 'on_the_way', 'at_location', 'working'])->default('idle');
            $table->boolean('is_online')->default(true);
            
            // Metadata
            $table->string('device_id')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('recorded_at');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['service_provider_id', 'recorded_at']);
            $table->index(['task_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_location_tracking');
    }
};