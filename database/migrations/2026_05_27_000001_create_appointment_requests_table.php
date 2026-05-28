<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_persona_id');
            $table->date('preferred_date');
            $table->string('preferred_time', 10)->nullable();
            $table->string('reason', 300)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_appointment_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_persona_id')->references('id')->on('personas');
            $table->foreign('approved_appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index('patient_persona_id', 'idx_appt_req_patient');
            $table->index('status', 'idx_appt_req_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_requests');
    }
};
