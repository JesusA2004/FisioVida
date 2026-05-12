<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_persona_id');
            $table->string('type', 60); // tratamiento, fotografia, video, datos_clinicos, otro
            $table->string('version', 20)->default('1.0');
            $table->longText('content_snapshot')->nullable();
            $table->timestamp('accepted_at');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->string('signature_type', 30)->nullable(); // digital, fisica, verbal
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->timestamps();

            $table->foreign('patient_persona_id')->references('id')->on('personas')->cascadeOnDelete();
            $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('file_id')->references('id')->on('files')->nullOnDelete();

            $table->index('patient_persona_id', 'idx_consents_patient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
