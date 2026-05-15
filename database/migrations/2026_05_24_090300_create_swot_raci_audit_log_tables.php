<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('swot_audit_logs')) {
            Schema::create('swot_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('swot_template_id')->nullable()->constrained('swot_templates')->nullOnDelete();
                $table->foreignId('swot_analysis_id')->nullable()->constrained('swot_analyses')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_name')->index();
                $table->string('status')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('raci_audit_logs')) {
            Schema::create('raci_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raci_template_id')->nullable()->constrained('raci_templates')->nullOnDelete();
                $table->foreignId('raci_matrix_id')->nullable()->constrained('raci_matrices')->nullOnDelete();
                $table->foreignId('raci_assignment_id')->nullable()->constrained('raci_assignments')->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_name')->index();
                $table->string('status')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raci_audit_logs');
        Schema::dropIfExists('swot_audit_logs');
    }
};
