<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_module_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->unique()->constrained('departments')->cascadeOnDelete();
            $table->enum('default_role', ['viewer', 'contributor', 'validator', 'administrator'])->default('viewer');
            $table->boolean('inherit_to_children')->default(true);
            $table->boolean('active')->default(true);
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('process_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_department_id')->constrained('departments')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('institutional_processes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('domain_id')->constrained('process_domains')->restrictOnDelete();
            $table->foreignId('owner_department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->text('description')->nullable();
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->enum('status', ['draft', 'pending_validation', 'published', 'revision', 'archived'])->default('draft');
            $table->enum('visibility', ['owner', 'participants', 'institutional'])->default('participants');
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedTinyInteger('maturity_level')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'visibility'], 'inst_proc_status_visibility_idx');
        });

        Schema::create('institutional_process_department', function (Blueprint $table): void {
            $table->foreignId('institutional_process_id')->constrained('institutional_processes', indexName: 'inst_proc_dept_process_fk')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->enum('participation_role', ['participant', 'consulted', 'informed'])->default('participant');
            $table->primary(['institutional_process_id', 'department_id'], 'inst_proc_department_pk');
        });

        Schema::create('process_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institutional_process_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('produced_documents')->nullable();
            $table->timestamps();
            $table->unique(['institutional_process_id', 'sequence'], 'proc_activity_sequence_uq');
        });

        Schema::create('process_elements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institutional_process_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['input', 'output', 'actor', 'application', 'asset']);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['institutional_process_id', 'type'], 'proc_element_type_idx');
        });

        Schema::create('process_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institutional_process_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('process_kpis', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institutional_process_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 40)->nullable();
            $table->decimal('target_value', 14, 2)->nullable();
            $table->decimal('current_value', 14, 2)->nullable();
            $table->text('calculation_method')->nullable();
            $table->timestamps();
        });

        Schema::create('process_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institutional_process_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->unsignedSmallInteger('version');
            $table->json('changes')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->index(['institutional_process_id', 'occurred_at'], 'proc_history_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_history');
        Schema::dropIfExists('process_kpis');
        Schema::dropIfExists('process_documents');
        Schema::dropIfExists('process_elements');
        Schema::dropIfExists('process_activities');
        Schema::dropIfExists('institutional_process_department');
        Schema::dropIfExists('institutional_processes');
        Schema::dropIfExists('process_domains');
        Schema::dropIfExists('process_module_accesses');
    }
};
