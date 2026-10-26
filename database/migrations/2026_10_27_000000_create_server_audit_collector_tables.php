<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_audit_control_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('methodology_requirement_id')->nullable();
            $table->foreign('methodology_requirement_id', 'sa_control_requirement_fk')->references('id')->on('methodology_requirements')->nullOnDelete();
            $table->foreignId('control_measure_id')->nullable();
            $table->foreign('control_measure_id', 'sa_control_measure_fk')->references('id')->on('control_measures')->nullOnDelete();
            $table->string('code', 96)->unique();
            $table->unsignedInteger('version')->default(1);
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('criticality', 24);
            $table->text('control_objective')->nullable();
            $table->text('default_expected')->nullable();
            $table->text('risk_description')->nullable();
            $table->text('treatment_guidance');
            $table->json('applicability')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('server_audit_control_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_audit_control_definition_id');
            $table->foreign('server_audit_control_definition_id', 'sa_reference_definition_fk')->references('id')->on('server_audit_control_definitions')->cascadeOnDelete();
            $table->string('authority', 100);
            $table->string('document_title');
            $table->string('document_version')->nullable();
            $table->string('chapter')->nullable();
            $table->string('section')->nullable();
            $table->string('provision_reference')->nullable();
            $table->text('requirement_summary')->nullable();
            $table->text('source_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('server_audit_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('asset_uuid');
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->string('hostname');
            $table->string('ip_address', 45)->nullable();
            $table->string('platform', 64);
            $table->string('asset_type')->default('server');
            $table->string('product')->nullable();
            $table->string('product_version')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('asset_role')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['mission_id', 'asset_uuid']);
            $table->index(['mission_id', 'platform']);
        });

        Schema::create('server_audit_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_audit_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('raw_document_id')->nullable()->constrained('mission_documents')->nullOnDelete();
            $table->string('collector_version', 32);
            $table->string('status', 32)->default('received');
            $table->string('source_ip', 45)->nullable();
            $table->string('payload_sha256', 64);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->index(['mission_id', 'created_at']);
        });

        Schema::create('server_audit_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_audit_run_id')->constrained()->cascadeOnDelete();
            $table->string('control_code', 96);
            $table->foreignId('server_audit_control_definition_id')->nullable();
            $table->foreign('server_audit_control_definition_id', 'sa_result_definition_fk')->references('id')->on('server_audit_control_definitions')->nullOnDelete();
            $table->string('status', 24);
            $table->string('criticality', 24)->nullable();
            $table->text('expected')->nullable();
            $table->longText('observed')->nullable();
            $table->longText('raw_output')->nullable();
            $table->foreignId('identified_risk_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['server_audit_run_id', 'control_code']);
            $table->index(['control_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_audit_results');
        Schema::dropIfExists('server_audit_runs');
        Schema::dropIfExists('server_audit_assets');
        Schema::dropIfExists('server_audit_control_references');
        Schema::dropIfExists('server_audit_control_definitions');
    }
};
