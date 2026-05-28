<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_legal_acceptances', function (Blueprint $table) {
            $table->string('signer_name', 200)->nullable()->after('accepted_by_name');
            $table->string('signer_role', 30)->nullable()->after('signer_name');
            // patient | guardian | staff
            $table->string('signature_method', 40)->nullable()->after('signer_role');
            // drawn_signature | staff_recorded | checkbox
            $table->string('signature_image_path', 500)->nullable()->after('signature_method');
            $table->string('signature_hash', 128)->nullable()->after('signature_image_path');
            $table->string('document_hash', 128)->nullable()->after('signature_hash');
            $table->string('signed_pdf_path', 500)->nullable()->after('document_hash');
            $table->timestamp('signed_at')->nullable()->after('signed_pdf_path');
            $table->string('signed_ip', 45)->nullable()->after('signed_at');
            $table->string('signed_user_agent', 500)->nullable()->after('signed_ip');
            $table->json('acceptance_metadata')->nullable()->after('signed_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('patient_legal_acceptances', function (Blueprint $table) {
            $table->dropColumn([
                'signer_name', 'signer_role', 'signature_method',
                'signature_image_path', 'signature_hash', 'document_hash',
                'signed_pdf_path', 'signed_at', 'signed_ip',
                'signed_user_agent', 'acceptance_metadata',
            ]);
        });
    }
};
