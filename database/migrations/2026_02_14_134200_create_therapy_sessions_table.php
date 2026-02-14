<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('therapy_sessions', function (Blueprint $table){
            $table->id();

            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('therapist_user_id')->constrained('users')->restrictOnDelete();

            $table->date('session_date');

            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();

            $table->unsignedTinyInteger('pain_scale')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['patient_persona_id', 'session_date'], 'idx_sessions_patient_date');
            $table->index(['therapist_user_id', 'session_date'], 'idx_sessions_therapist_date');
        });
    }

    public function down(): void {
        Schema::dropIfExists('therapy_sessions');
    }

};
