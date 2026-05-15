<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->string('context_type')->index();
                $table->string('title')->nullable();
                $table->string('status')->default('active')->index();
                $table->json('context_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_prompt_templates')) {
            Schema::create('ai_prompt_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('slug')->index();
                $table->string('name');
                $table->string('context_type')->index();
                $table->text('system_prompt');
                $table->text('user_prompt_template')->nullable();
                $table->boolean('active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['slug', 'department_id'], 'ai_prompt_templates_slug_dept_unique');
            });
        }

        if (! Schema::hasTable('ai_recommendations')) {
            Schema::create('ai_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('recommendation_type')->index();
                $table->string('confidence_level')->index();
                $table->string('title');
                $table->text('summary');
                $table->text('rationale')->nullable();
                $table->json('payload')->nullable();
                $table->boolean('requires_human_validation')->default(true);
                $table->boolean('accepted')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_analysis_snapshots')) {
            Schema::create('ai_analysis_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->string('context_type')->index();
                $table->string('analysis_scope')->index();
                $table->json('input_snapshot')->nullable();
                $table->json('output_snapshot')->nullable();
                $table->string('confidence_level')->nullable();
                $table->string('driver')->nullable();
                $table->string('integrity_hash')->nullable()->index();
                $table->timestamp('captured_at')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_execution_logs')) {
            Schema::create('ai_execution_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('driver')->index();
                $table->string('status')->index();
                $table->string('prompt_hash')->nullable()->index();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedInteger('token_estimate')->nullable();
                $table->json('request_meta')->nullable();
                $table->json('response_meta')->nullable();
                $table->timestamp('executed_at')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_execution_logs');
        Schema::dropIfExists('ai_analysis_snapshots');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_conversations');
    }
};
