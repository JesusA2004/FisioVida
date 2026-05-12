<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->date('retention_until')->nullable()->after('deleted_at');
            $table->timestamp('purged_at')->nullable()->after('retention_until');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'updated_at', 'deleted_at', 'retention_until', 'purged_at']);
        });
    }
};
