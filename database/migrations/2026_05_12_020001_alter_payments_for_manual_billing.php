<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_persona_id')->nullable()->after('id');
            $table->string('concept', 80)->nullable()->after('patient_persona_id');
            $table->string('payment_method', 30)->nullable()->after('currency');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->timestamp('cancelled_at')->nullable()->after('created_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');

            $table->foreign('patient_persona_id')->references('id')->on('personas')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();

            $table->index('patient_persona_id', 'idx_pay_patient');
        });

        // MySQL-specific column modifications
        DB::statement("ALTER TABLE payments MODIFY COLUMN provider VARCHAR(50) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'paid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE payments MODIFY COLUMN provider VARCHAR(50) NOT NULL DEFAULT ''");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['patient_persona_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex('idx_pay_patient');
            $table->dropColumn(['patient_persona_id', 'concept', 'payment_method', 'created_by', 'cancelled_at', 'cancelled_by']);
        });
    }
};
