<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('swot_templates')) {
            Schema::create('swot_templates', function (Blueprint $table) {
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
                $table->json('weighting_profile')->nullable();
                $table->json('metadata')->nullable();
                $table->string('signature_hash')->nullable()->index();
                $table->foreignId('source_template_id')->nullable()->constrained('swot_templates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['slug', 'version'], 'swot_templates_slug_version_unique');
            });
        }

        if (! Schema::hasTable('swot_categories')) {
            Schema::create('swot_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->constrained('swot_templates')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->string('category_type')->index();
                $table->text('description')->nullable();
                $table->decimal('weight', 8, 2)->default(1);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('swot_entries')) {
            Schema::create('swot_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->constrained('swot_templates')->cascadeOnDelete();
                $table->foreignId('swot_category_id')->nullable()->constrained('swot_categories')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('impact_level')->default('medium')->index();
                $table->string('priority_level')->default('medium')->index();
                $table->decimal('weight', 8, 2)->default(1);
                $table->string('source_type')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('swot_analyses')) {
            Schema::create('swot_analyses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->nullable()->constrained('swot_templates')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->foreignId('workflow_stage_execution_id')->nullable()->constrained('workflow_stage_executions')->nullOnDelete();
                $table->string('analysis_scope')->default('mission')->index();
                $table->string('status')->default('draft')->index();
                $table->decimal('score', 10, 2)->default(0);
                $table->decimal('weighted_score', 10, 2)->default(0);
                $table->decimal('priority_index', 10, 2)->default(0);
                $table->json('analysis_payload')->nullable();
                $table->timestamp('concluded_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('swot_recommendations')) {
            Schema::create('swot_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->nullable()->constrained('swot_templates')->nullOnDelete();
                $table->foreignId('swot_analysis_id')->nullable()->constrained('swot_analyses')->nullOnDelete();
                $table->foreignId('swot_entry_id')->nullable()->constrained('swot_entries')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority_level')->default('medium')->index();
                $table->decimal('priority_index', 10, 2)->default(0);
                $table->string('owner_role')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->json('metadata')->nullable();
                $table->date('due_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('swot_snapshots')) {
            Schema::create('swot_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->nullable()->constrained('swot_templates')->nullOnDelete();
                $table->foreignId('swot_analysis_id')->nullable()->constrained('swot_analyses')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->string('snapshot_hash')->index();
                $table->json('snapshot_payload');
                $table->timestamp('captured_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('swot_snapshots');
        Schema::dropIfExists('swot_recommendations');
        Schema::dropIfExists('swot_analyses');
        Schema::dropIfExists('swot_entries');
        Schema::dropIfExists('swot_categories');
        Schema::dropIfExists('swot_templates');
    }
};
