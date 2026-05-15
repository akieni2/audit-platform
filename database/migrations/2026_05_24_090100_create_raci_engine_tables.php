<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('raci_templates')) {
            Schema::create('raci_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('code')->nullable()->index();
                $table->text('description')->nullable();
                $table->string('analysis_scope')->default('mission')->index();
                $table->boolean('active')->default(true);
                $table->boolean('is_global')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->string('lifecycle_status')->default('draft')->index();
                $table->json('metadata')->nullable();
                $table->string('signature_hash')->nullable()->index();
                $table->foreignId('source_template_id')->nullable()->constrained('raci_templates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['slug', 'version'], 'raci_templates_slug_version_unique');
            });
        }

        if (! Schema::hasTable('raci_matrices')) {
            Schema::create('raci_matrices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->constrained('raci_templates')->cascadeOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('process_label')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('raci_roles')) {
            Schema::create('raci_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->constrained('raci_templates')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->string('role_type')->default('responsible')->index();
                $table->string('responsibility_level')->default('moderate')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('raci_assignments')) {
            Schema::create('raci_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->constrained('raci_templates')->cascadeOnDelete();
                $table->foreignId('raci_matrix_id')->nullable()->constrained('raci_matrices')->nullOnDelete();
                $table->foreignId('raci_role_id')->nullable()->constrained('raci_roles')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('process_label')->nullable()->index();
                $table->unsignedInteger('process_sort_order')->default(0);
                $table->string('role_type')->default('responsible')->index();
                $table->string('responsibility_level')->default('moderate')->index();
                $table->string('status')->default('draft')->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('raci_snapshots')) {
            Schema::create('raci_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->nullable()->constrained('raci_templates')->nullOnDelete();
                $table->foreignId('raci_matrix_id')->nullable()->constrained('raci_matrices')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->string('snapshot_hash')->index();
                $table->json('snapshot_payload');
                $table->timestamp('captured_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('raci_validations')) {
            Schema::create('raci_validations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->nullable()->constrained('raci_templates')->nullOnDelete();
                $table->foreignId('raci_matrix_id')->nullable()->constrained('raci_matrices')->nullOnDelete();
                $table->foreignId('raci_assignment_id')->nullable()->constrained('raci_assignments')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('validator_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('draft')->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raci_validations');
        Schema::dropIfExists('raci_snapshots');
        Schema::dropIfExists('raci_assignments');
        Schema::dropIfExists('raci_roles');
        Schema::dropIfExists('raci_matrices');
        Schema::dropIfExists('raci_templates');
    }
};
