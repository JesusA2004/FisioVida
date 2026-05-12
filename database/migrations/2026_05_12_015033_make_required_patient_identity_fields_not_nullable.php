<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fill placeholder values in ALL personas so NOT NULL alteration succeeds
        DB::table('personas')
            ->where(function ($query) {
                $query->whereNull('apellido_paterno')->orWhere('apellido_paterno', '');
            })
            ->update(['apellido_paterno' => 'Sin dato']);

        DB::table('personas')
            ->where(function ($query) {
                $query->whereNull('apellido_materno')->orWhere('apellido_materno', '');
            })
            ->update(['apellido_materno' => 'Sin dato']);

        DB::table('personas')
            ->where(function ($query) {
                $query->whereNull('telefono')->orWhere('telefono', '');
            })
            ->update(['telefono' => 'Sin dato']);

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
