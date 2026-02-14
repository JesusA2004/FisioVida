<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('therapy_sessions')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('disk', 30)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 190);
            $table->string('mime', 80)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['patient_persona_id', 'created_at'], 'idx_files_patient');
        });
    }

    public function down(): void {
        Schema::dropIfExists('files');
    }

};
