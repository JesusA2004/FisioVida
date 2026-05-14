<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_persona_id');
            $table->string('consent_type', 60); // tratamiento, privacidad, imagenes, datos_sensibles
            $table->timestamp('accepted_at');
            $table->unsignedBigInteger('accepted_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('patient_persona_id')->references('id')->on('personas')->cascadeOnDelete();
            $table->foreign('accepted_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['patient_persona_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consents');
    }
};
