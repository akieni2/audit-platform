<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_module_accesses', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('department_id')->unique()->constrained('departments')->cascadeOnDelete();
            $t->enum('default_role', ['viewer', 'contributor', 'validator', 'administrator'])->default('viewer');
            $t->boolean('inherit_to_children')->default(true);
            $t->boolean('active')->default(true);
            $t->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('institutional_asset_categories', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('owner_department_id')->constrained('departments')->restrictOnDelete();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('institutional_assets', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('category_id')->constrained('institutional_asset_categories')->restrictOnDelete();
            $t->foreignId('owner_department_id')->constrained('departments')->restrictOnDelete();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('asset_tag', 60)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->date('commissioned_at')->nullable();
            $t->string('manufacturer')->nullable();
            $t->string('model')->nullable();
            $t->string('serial_number')->nullable()->index();
            $t->enum('condition', ['new', 'good', 'degraded', 'failed', 'retired'])->default('good');
            $t->enum('status', ['draft', 'active', 'maintenance', 'unavailable', 'retired', 'archived'])->default('draft');
            $t->decimal('estimated_value', 16, 2)->nullable();
            $t->unsignedTinyInteger('availability_score')->default(1);
            $t->unsignedTinyInteger('confidentiality_score')->default(1);
            $t->unsignedTinyInteger('integrity_score')->default(1);
            $t->unsignedTinyInteger('traceability_score')->default(1);
            $t->unsignedTinyInteger('probability_score')->default(1);
            $t->unsignedTinyInteger('impact_score')->default(1);
            $t->unsignedSmallInteger('criticality_score')->default(1);
            $t->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('low');
            $t->text('interrupted_services')->nullable();
            $t->text('impacted_users')->nullable();
            $t->text('impacted_applications')->nullable();
            $t->text('fallback_solution')->nullable();
            $t->unsignedInteger('rto_minutes')->nullable();
            $t->unsignedInteger('rpo_minutes')->nullable();
            $t->boolean('has_backup')->default(false);
            $t->boolean('has_redundancy')->default(false);
            $t->boolean('single_point_of_failure')->default(false);
            $t->boolean('obsolete')->default(false);
            $t->enum('visibility', ['owner', 'participants', 'institutional'])->default('participants');
            $t->json('specific_attributes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['criticality', 'status'], 'inst_asset_critical_status_idx');
        });
        Schema::create('institutional_asset_department', function (Blueprint $t): void {
            $t->foreignId('institutional_asset_id')->constrained('institutional_assets', indexName: 'inst_asset_dept_asset_fk')->cascadeOnDelete();
            $t->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $t->primary(['institutional_asset_id', 'department_id'], 'inst_asset_department_pk');
        });
        Schema::create('institutional_asset_dependencies', function (Blueprint $t): void {
            $t->foreignId('asset_id')->constrained('institutional_assets', indexName: 'asset_dependency_source_fk')->cascadeOnDelete();
            $t->foreignId('depends_on_asset_id')->constrained('institutional_assets', indexName: 'asset_dependency_target_fk')->cascadeOnDelete();
            $t->string('dependency_type')->nullable();
            $t->text('description')->nullable();
            $t->boolean('critical')->default(false);
            $t->primary(['asset_id', 'depends_on_asset_id'], 'inst_asset_dependency_pk');
        });
        Schema::create('institutional_asset_process', function (Blueprint $t): void {
            $t->foreignId('institutional_asset_id')->constrained('institutional_assets', indexName: 'asset_process_asset_fk')->cascadeOnDelete();
            $t->foreignId('institutional_process_id')->constrained('institutional_processes', indexName: 'asset_process_process_fk')->cascadeOnDelete();
            $t->primary(['institutional_asset_id', 'institutional_process_id'], 'inst_asset_process_pk');
        });
        Schema::create('institutional_asset_controls', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('institutional_asset_id')->constrained('institutional_assets', indexName: 'asset_control_asset_fk')->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->enum('status', ['planned', 'implemented', 'ineffective', 'not_applicable'])->default('planned');
            $t->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->date('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('institutional_asset_documents', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('institutional_asset_id')->constrained('institutional_assets', indexName: 'asset_document_asset_fk')->cascadeOnDelete();
            $t->string('title');
            $t->string('document_type')->nullable();
            $t->string('path');
            $t->string('original_name');
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('institutional_asset_history', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('institutional_asset_id')->constrained('institutional_assets', indexName: 'asset_history_asset_fk')->cascadeOnDelete();
            $t->string('event_type', 60);
            $t->json('changes')->nullable();
            $t->text('comment')->nullable();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('occurred_at');
            $t->index(['institutional_asset_id', 'occurred_at'], 'asset_history_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_asset_history');
        Schema::dropIfExists('institutional_asset_documents');
        Schema::dropIfExists('institutional_asset_controls');
        Schema::dropIfExists('institutional_asset_process');
        Schema::dropIfExists('institutional_asset_dependencies');
        Schema::dropIfExists('institutional_asset_department');
        Schema::dropIfExists('institutional_assets');
        Schema::dropIfExists('institutional_asset_categories');
        Schema::dropIfExists('asset_module_accesses');
    }
};
