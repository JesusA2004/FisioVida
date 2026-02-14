<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190);
            $table->text('description')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name'], 'idx_exercises_active');
        });
    }

    public function down(): void {
        Schema::dropIfExists('exercises');
    }

};
