<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('provider', 50);
            $table->string('provider_payment_id', 120)->nullable();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('MXN');

            $table->enum('status', ['pending','paid','failed','refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->string('reference', 120)->nullable();
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_pay_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('payments');
    }

};
