<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_templates')) {
            Schema::create('form_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('component_key', 80)->nullable();
                $table->json('department_scope')->nullable();
                $table->boolean('active')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->string('lifecycle_status', 32)->default('draft');
                $table->string('signature_hash', 64)->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('deprecated_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('source_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['lifecycle_status', 'active']);
            });
        }

        if (! Schema::hasTable('form_fields')) {
            Schema::create('form_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
                $table->string('field_key', 120);
                $table->string('label');
                $table->text('help_text')->nullable();
                $table->string('field_type', 64);
                $table->string('placeholder')->nullable();
                $table->text('default_value')->nullable();
                $table->json('configuration_json')->nullable();
                $table->json('validation_rules_json')->nullable();
                $table->json('conditional_rules_json')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_required')->default(false);
                $table->boolean('is_repeatable')->default(false);
                $table->boolean('active')->default(true);
                $table->foreignId('source_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['form_template_id', 'sort_order']);
                $table->unique(['form_template_id', 'field_key']);
            });
        }

        if (! Schema::hasTable('form_field_options')) {
            Schema::create('form_field_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_field_id')->constrained('form_fields')->cascadeOnDelete();
                $table->string('label');
                $table->string('value');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->foreignId('source_option_id')->nullable()->constrained('form_field_options')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['form_field_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('form_submissions')) {
            Schema::create('form_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
                $table->foreignId('workflow_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
                $table->foreignId('workflow_stage_execution_id')->nullable()->constrained('workflow_stage_executions')->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->cascadeOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained('missions')->nullOnDelete();
                $table->foreignId('entretien_id')->nullable()->constrained('entretiens')->nullOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->string('status', 32)->default('draft');
                $table->json('submission_payload')->nullable();
                $table->json('form_snapshot')->nullable();
                $table->json('validation_errors_json')->nullable();
                $table->timestamps();

                $table->index(['workflow_instance_id', 'workflow_stage_id']);
                $table->index(['mission_id', 'entretien_id']);
                $table->index(['status', 'submitted_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_field_options');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_templates');
    }
};
