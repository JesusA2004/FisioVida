<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_persona_id');
            $table->unsignedBigInteger('user_id')->nullable();

            // Tipo de documento
            $table->string('document_type', 60);
            // privacy_notice | sensitive_data | treatment_consent | image_consent | minor_consent

            $table->string('version', 20)->default('1.0');
            $table->string('title', 200)->nullable();
            $table->longText('content_snapshot')->nullable(); // snapshot del texto exacto aceptado

            // Quién y cuándo aceptó
            $table->timestamp('accepted_at');
            $table->unsignedBigInteger('accepted_by_user_id')->nullable(); // staff que registró
            $table->string('accepted_by_name', 200)->nullable();

            // Para menores de edad
            $table->string('guardian_name', 200)->nullable();
            $table->string('guardian_relationship', 80)->nullable();

            // Metadata de trazabilidad
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('source', 30)->default('staff'); // staff | portal | printed

            // Estado del consentimiento
            $table->string('status', 20)->default('accepted'); // accepted | revoked
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();

            $table->timestamps();

            $table->foreign('patient_persona_id')->references('id')->on('personas')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('accepted_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['patient_persona_id', 'document_type']);
            $table->index(['patient_persona_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_legal_acceptances');
    }
};
