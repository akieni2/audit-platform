<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_templates')) {
            Schema::create('workflow_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('name');
                $table->string('slug', 120);
                $table->text('description')->nullable();
                $table->string('code', 64)->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->string('status', 32)->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['department_id', 'status']);
                $table->index(['slug', 'version']);
                $table->index(['code', 'active']);
            });
        }

        if (! Schema::hasTable('workflow_stages')) {
            Schema::create('workflow_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 64);
                $table->text('description')->nullable();
                $table->string('stage_type', 64);
                $table->integer('sort_order')->default(0);
                $table->json('configuration')->nullable();
                $table->boolean('is_required')->default(true);
                $table->boolean('is_repeatable')->default(false);
                $table->string('role_scope', 120)->nullable();
                $table->timestamps();

                $table->index(['workflow_template_id', 'sort_order']);
                $table->index(['workflow_template_id', 'code']);
            });
        }

        if (! Schema::hasTable('workflow_transitions')) {
            Schema::create('workflow_transitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
                $table->foreignId('from_stage_id')->constrained('workflow_stages')->cascadeOnDelete();
                $table->foreignId('to_stage_id')->constrained('workflow_stages')->cascadeOnDelete();
                $table->string('condition_type', 64)->nullable();
                $table->json('condition_configuration')->nullable();
                $table->string('role_required', 120)->nullable();
                $table->boolean('is_automatic')->default(false);
                $table->timestamps();

                $table->index(['workflow_template_id', 'from_stage_id']);
                $table->index(['workflow_template_id', 'to_stage_id']);
            });
        }

        if (! Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
                $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
                $table->foreignId('current_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
                $table->string('status', 32)->default('draft');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['mission_id', 'status']);
                $table->index(['workflow_template_id', 'status']);
            });
        }

        if (! Schema::hasTable('workflow_stage_executions')) {
            Schema::create('workflow_stage_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
                $table->foreignId('workflow_stage_id')->constrained('workflow_stages')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['workflow_instance_id', 'status']);
                $table->index(['workflow_stage_id', 'status']);
            });
        }

        if (Schema::hasTable('missions') && ! Schema::hasColumn('missions', 'workflow_instance_id')) {
            Schema::table('missions', function (Blueprint $table) {
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('missions') && Schema::hasColumn('missions', 'workflow_instance_id')) {
            Schema::table('missions', function (Blueprint $table) {
                $table->dropForeign(['workflow_instance_id']);
                $table->dropColumn('workflow_instance_id');
            });
        }

        Schema::dropIfExists('workflow_stage_executions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('workflow_templates');
    }
};
