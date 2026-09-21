<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_document_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('questionnaire_question_id')->nullable()->constrained('questionnaire_questions')->nullOnDelete();
            $table->foreignId('mission_audit_group_id')->nullable()->constrained('mission_audit_groups')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('label', 512);
            $table->string('category', 128)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('priority', 16)->default('normal');
            $table->boolean('is_required')->default(true);
            $table->timestamp('requested_at')->nullable();
            $table->date('due_at')->nullable()->index();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mission_id', 'service_id'], 'document_request_mission_service_index');
            $table->index(['questionnaire_question_id', 'service_id'], 'document_request_question_service_index');
        });

        Schema::table('mission_documents', function (Blueprint $table): void {
            $table->foreignId('mission_document_request_id')->nullable()->after('mission_audit_group_id')
                ->constrained('mission_document_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mission_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mission_document_request_id');
        });

        Schema::dropIfExists('mission_document_requests');
    }
};
