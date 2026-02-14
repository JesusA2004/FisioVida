<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // OJO: users de Breeze ya trae timestamps, rememberToken, etc.
            $table->foreignId('persona_id')->nullable()->after('id')->constrained('personas')->nullOnDelete();

            $table->enum('status', ['active', 'blocked'])->default('active')->after('password');
            $table->boolean('is_super_admin')->default(false)->after('status');

            // flags por módulo
            $table->boolean('mod_agenda')->default(true)->after('is_super_admin');
            $table->boolean('mod_pacientes')->default(true)->after('mod_agenda');
            $table->boolean('mod_sesiones')->default(true)->after('mod_pacientes');
            $table->boolean('mod_ejercicios')->default(true)->after('mod_sesiones');
            $table->boolean('mod_archivos')->default(true)->after('mod_ejercicios');
            $table->boolean('mod_reportes')->default(false)->after('mod_archivos');
            $table->boolean('mod_cobranza')->default(false)->after('mod_reportes');
            $table->boolean('mod_config')->default(false)->after('mod_cobranza');

            $table->timestamp('last_login_at')->nullable()->after('mod_config');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // Breeze ya trae softDeletes? normalmente NO.
            $table->softDeletes()->after('updated_at');

            $table->index(['status'], 'idx_users_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // primero FK
            $table->dropForeign(['persona_id']);

            $columns = [
                'persona_id',
                'status',
                'is_super_admin',
                'mod_agenda',
                'mod_pacientes',
                'mod_sesiones',
                'mod_ejercicios',
                'mod_archivos',
                'mod_reportes',
                'mod_cobranza',
                'mod_config',
                'last_login_at',
                'last_login_ip',
                'deleted_at',
            ];

            // índice
            $table->dropIndex('idx_users_status');

            $table->dropColumn($columns);
        });
    }

};
