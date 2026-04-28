<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personas') || Schema::hasColumn('personas', 'email')) {
            return;
        }

        Schema::table('personas', function (Blueprint $table) {
            $table->string('email', 190)->nullable()->after('telefono');
            $table->index('email', 'idx_personas_email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personas') || ! Schema::hasColumn('personas', 'email')) {
            return;
        }

        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex('idx_personas_email');
            $table->dropColumn('email');
        });
    }
};
