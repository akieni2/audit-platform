<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('methodology_templates')) {
            Schema::create('methodology_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('default_workflow_template_id')->nullable()->constrained('workflow_templates')->nullOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('framework_key')->index();
                $table->string('code')->nullable()->index();
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_global')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->string('lifecycle_status')->default('draft')->index();
                $table->json('department_scope')->nullable();
                $table->json('metadata')->nullable();
                $table->string('signature_hash')->nullable()->index();
                $table->foreignId('source_template_id')->nullable()->constrained('methodology_templates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('deprecated_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['slug', 'version'], 'methodology_templates_slug_version_unique');
            });
        }

        if (! Schema::hasTable('taxonomies')) {
            Schema::create('taxonomies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('taxonomy_type')->index();
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('is_national')->default(false);
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('control_libraries')) {
            Schema::create('control_libraries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('methodology_template_id')->nullable()->constrained('methodology_templates')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('visibility_scope')->nullable()->index();
                $table->boolean('active')->default(true);
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('methodology_categories')) {
            Schema::create('methodology_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('methodology_template_id')->constrained('methodology_templates')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('methodology_categories')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('methodology_controls')) {
            Schema::create('methodology_controls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('methodology_template_id')->constrained('methodology_templates')->cascadeOnDelete();
                $table->foreignId('methodology_category_id')->nullable()->constrained('methodology_categories')->nullOnDelete();
                $table->string('control_reference')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('control_type')->nullable()->index();
                $table->string('criticality')->nullable()->index();
                $table->string('default_workflow_stage_code')->nullable()->index();
                $table->text('control_objective')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('methodology_requirements')) {
            Schema::create('methodology_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('methodology_template_id')->constrained('methodology_templates')->cascadeOnDelete();
                $table->foreignId('methodology_category_id')->nullable()->constrained('methodology_categories')->nullOnDelete();
                $table->foreignId('methodology_control_id')->nullable()->constrained('methodology_controls')->nullOnDelete();
                $table->string('requirement_reference')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->string('applicability_scope')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('taxonomy_terms')) {
            Schema::create('taxonomy_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->text('description')->nullable();
                $table->json('alias_terms')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('control_measures')) {
            Schema::create('control_measures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('control_library_id')->constrained('control_libraries')->cascadeOnDelete();
                $table->foreignId('methodology_control_id')->nullable()->constrained('methodology_controls')->nullOnDelete();
                $table->foreignId('taxonomy_term_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('execution_frequency')->nullable();
                $table->string('owner_role')->nullable();
                $table->unsignedInteger('maturity_level')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('methodology_mappings')) {
            Schema::create('methodology_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('methodology_template_id')->nullable()->constrained('methodology_templates')->nullOnDelete();
                $table->foreignId('methodology_control_id')->nullable()->constrained('methodology_controls')->nullOnDelete();
                $table->foreignId('methodology_requirement_id')->nullable()->constrained('methodology_requirements')->nullOnDelete();
                $table->foreignId('workflow_template_id')->nullable()->constrained('workflow_templates')->nullOnDelete();
                $table->foreignId('workflow_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
                $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
                $table->foreignId('questionnaire_template_id')->nullable()->constrained('questionnaire_templates')->nullOnDelete();
                $table->foreignId('control_library_id')->nullable()->constrained('control_libraries')->nullOnDelete();
                $table->foreignId('control_measure_id')->nullable()->constrained('control_measures')->nullOnDelete();
                $table->foreignId('taxonomy_term_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('mapping_type')->index();
                $table->string('risk_category')->nullable()->index();
                $table->json('mapping_payload')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('taxonomy_mappings')) {
            Schema::create('taxonomy_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxonomy_id')->nullable()->constrained('taxonomies')->nullOnDelete();
                $table->foreignId('taxonomy_term_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->nullableMorphs('mappable');
                $table->string('mapping_type')->default('direct')->index();
                $table->string('external_reference')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('control_evidences')) {
            Schema::create('control_evidences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('control_measure_id')->constrained('control_measures')->cascadeOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->foreignId('form_submission_id')->nullable()->constrained('form_submissions')->nullOnDelete();
                $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('evidence_type')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('document_path')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('collected_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('control_executions')) {
            Schema::create('control_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('control_measure_id')->constrained('control_measures')->cascadeOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->foreignId('workflow_stage_execution_id')->nullable()->constrained('workflow_stage_executions')->nullOnDelete();
                $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending')->index();
                $table->unsignedInteger('score')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('control_executions');
        Schema::dropIfExists('control_evidences');
        Schema::dropIfExists('taxonomy_mappings');
        Schema::dropIfExists('methodology_mappings');
        Schema::dropIfExists('control_measures');
        Schema::dropIfExists('taxonomy_terms');
        Schema::dropIfExists('methodology_requirements');
        Schema::dropIfExists('methodology_controls');
        Schema::dropIfExists('methodology_categories');
        Schema::dropIfExists('control_libraries');
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('methodology_templates');
    }
};
