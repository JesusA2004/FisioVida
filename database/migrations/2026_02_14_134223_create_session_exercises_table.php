<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('session_exercises', function (Blueprint $table) {
            $table->foreignId('session_id')->constrained('therapy_sessions')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->restrictOnDelete();

            $table->unsignedInteger('sets')->nullable();
            $table->unsignedInteger('reps')->nullable();
            $table->unsignedInteger('seconds')->nullable();
            $table->string('notes', 255)->nullable();

            $table->primary(['session_id', 'exercise_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('session_exercises');
    }
    
};
