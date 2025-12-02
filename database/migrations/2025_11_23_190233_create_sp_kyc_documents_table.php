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
        if (!Schema::hasTable('sp_kyc_documents')) {
            Schema::create('sp_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
            $table->enum('document_type', ['aadhaar', 'pan', 'bank_passbook', 'police_verification', 'photo', 'address_proof']);
            $table->string('document_number')->nullable();
            $table->string('document_url');
            $table->enum('verification_status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable(); // Store additional document info
            $table->timestamps();
            
            $table->index(['service_provider_id', 'document_type']);
            $table->index('verification_status');
            $table->index('expiry_date');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_kyc_documents');
    }
};
