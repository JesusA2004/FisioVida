<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            $table->enum('level', ['info','warning','error','audit'])->default('info');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 120);
            $table->string('entity_type', 80)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('message', 255);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['level', 'created_at'], 'idx_logs_level_time');
            $table->index(['actor_user_id', 'created_at'], 'idx_logs_actor_time');
        });
    }

    public function down(): void {
        Schema::dropIfExists('logs');
    }

};
