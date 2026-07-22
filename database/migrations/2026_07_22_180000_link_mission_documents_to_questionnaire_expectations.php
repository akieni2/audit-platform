<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_documents', function (Blueprint $table): void {
            $table->foreignId('questionnaire_question_id')->nullable()->after('entretien_id')
                ->constrained('questionnaire_questions')->nullOnDelete();
            $table->foreignId('mission_audit_group_id')->nullable()->after('questionnaire_question_id')
                ->constrained('mission_audit_groups')->nullOnDelete();
            $table->string('expected_document_label')->nullable()->after('category');
            $table->string('receipt_status', 32)->default('received')->after('expected_document_label');
            $table->string('checksum_sha256', 64)->nullable()->after('path');
            $table->timestamp('provided_at')->nullable()->after('version');
            $table->index(['mission_id', 'questionnaire_question_id'], 'mission_document_question_index');
        });
    }

    public function down(): void
    {
        Schema::table('mission_documents', function (Blueprint $table): void {
            $table->dropIndex('mission_document_question_index');
            $table->dropConstrainedForeignId('mission_audit_group_id');
            $table->dropConstrainedForeignId('questionnaire_question_id');
            $table->dropColumn(['expected_document_label', 'receipt_status', 'checksum_sha256', 'provided_at']);
        });
    }
};
