<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_audit_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->foreignId('questionnaire_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('interviewed_person')->nullable();
            $table->string('interviewed_role')->nullable();
            $table->text('objective')->nullable();
            $table->string('status', 32)->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mission_audit_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_audit_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_team_member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mission_audit_group_id', 'mission_team_member_id'], 'audit_group_member_unique');
        });

        Schema::create('questionnaire_document_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_audit_group_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('sha256', 64);
            $table->string('status', 32)->default('parsed');
            $table->json('extracted_data')->nullable();
            $table->json('analysis_suggestions')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_document_imports');
        Schema::dropIfExists('mission_audit_group_members');
        Schema::dropIfExists('mission_audit_groups');
    }
};
