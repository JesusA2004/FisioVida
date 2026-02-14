<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();

            $table->enum('tipo', ['paciente', 'staff', 'ambos'])->default('paciente');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->string('nombres', 120);
            $table->string('apellido_paterno', 120)->nullable();
            $table->string('apellido_materno', 120)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F', 'X'])->nullable();

            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();

            $table->string('contacto_emergencia_nombre', 190)->nullable();
            $table->string('contacto_emergencia_telefono', 30)->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['apellido_paterno'], 'idx_personas_apellido_paterno');
            $table->index(['apellido_materno'], 'idx_personas_apellido_materno');
            $table->index(['nombres'], 'idx_personas_nombres');
            $table->index(['tipo', 'status'], 'idx_personas_tipo');
        });
    }

    public function down(): void {
        Schema::dropIfExists('personas');
    }

};
