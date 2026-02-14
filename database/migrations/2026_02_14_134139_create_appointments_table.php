<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('therapist_user_id')->constrained('users')->restrictOnDelete();

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->enum('status', ['scheduled','confirmed','arrived','no_show','cancelled','done'])
                ->default('scheduled');

            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['therapist_user_id', 'start_at'], 'idx_appt_therapist_time');
            $table->index(['patient_persona_id', 'start_at'], 'idx_appt_patient_time');
        });
    }

    public function down(): void {
        Schema::dropIfExists('appointments');
    }

};
