<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'user_id')) $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('logs', 'module')) $table->string('module', 80)->nullable()->after('action');
            if (!Schema::hasColumn('logs', 'auditable_type')) $table->string('auditable_type', 120)->nullable()->after('module');
            if (!Schema::hasColumn('logs', 'auditable_id')) $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
            if (!Schema::hasColumn('logs', 'human_message')) $table->string('human_message', 500)->nullable()->after('message');
            if (!Schema::hasColumn('logs', 'old_values')) $table->json('old_values')->nullable()->after('human_message');
            if (!Schema::hasColumn('logs', 'new_values')) $table->json('new_values')->nullable()->after('old_values');
            if (!Schema::hasColumn('logs', 'ip_address')) $table->string('ip_address', 45)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            foreach (['new_values','old_values','human_message','auditable_id','auditable_type','module','ip_address'] as $col) {
                if (Schema::hasColumn('logs', $col)) $table->dropColumn($col);
            }
            if (Schema::hasColumn('logs', 'user_id')) $table->dropConstrainedForeignId('user_id');
        });
    }
};
