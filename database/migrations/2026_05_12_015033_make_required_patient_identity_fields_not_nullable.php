<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $missingRequiredData = DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where(function ($query) {
                $query
                    ->whereNull('apellido_paterno')
                    ->orWhere('apellido_paterno', '')
                    ->orWhereNull('apellido_materno')
                    ->orWhere('apellido_materno', '')
                    ->orWhereNull('telefono')
                    ->orWhere('telefono', '');
            })
            ->count();

        if ($missingRequiredData > 0) {
            throw new \RuntimeException(
                'No se puede aplicar la migración: existen pacientes activos sin apellido paterno, apellido materno o teléfono. Corrige esos registros antes de continuar.'
            );
        }

        DB::statement("
            ALTER TABLE personas
            MODIFY apellido_paterno VARCHAR(120) NOT NULL,
            MODIFY apellido_materno VARCHAR(120) NOT NULL,
            MODIFY telefono VARCHAR(30) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE personas
            MODIFY apellido_paterno VARCHAR(120) NULL,
            MODIFY apellido_materno VARCHAR(120) NULL,
            MODIFY telefono VARCHAR(30) NULL
        ");
    }
};