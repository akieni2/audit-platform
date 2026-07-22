<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->string('source_document_name')->nullable()->after('description');
            $table->string('source_document_path')->nullable()->after('source_document_name');
            $table->string('source_document_sha256', 64)->nullable()->after('source_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->dropColumn(['source_document_name', 'source_document_path', 'source_document_sha256']);
        });
    }
};
