<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_notice_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_persona_id');
            $table->string('version', 20)->default('1.0');
            $table->timestamp('accepted_at');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->foreign('patient_persona_id')->references('id')->on('personas')->cascadeOnDelete();
            $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete();

            $table->index('patient_persona_id', 'idx_privacy_patient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_notice_acceptances');
    }
};
