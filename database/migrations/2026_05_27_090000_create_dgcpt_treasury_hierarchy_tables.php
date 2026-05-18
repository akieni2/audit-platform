<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->string('entity_type', 32);
            $table->string('province')->nullable();
            $table->string('country', 64)->default('GA');
            $table->foreignId('parent_entity_id')->nullable()->constrained('treasury_entities')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'active']);
            $table->index('province');
        });

        Schema::create('treasury_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_entity_id')->constrained('treasury_entities')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->string('service_type', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['treasury_entity_id', 'code']);
            $table->index('service_type');
        });

        Schema::create('audit_domains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->text('description')->nullable();
            $table->foreignId('audit_domain_id')->nullable()->constrained('audit_domains')->nullOnDelete();
            $table->foreignId('questionnaire_template_id')->nullable()->constrained('questionnaire_templates')->nullOnDelete();
            $table->foreignId('workflow_template_id')->nullable()->constrained('workflow_templates')->nullOnDelete();
            $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
            $table->json('applicable_entity_types')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('treasury_entity_id')->nullable()->after('department_id')->constrained('treasury_entities')->nullOnDelete();
            $table->foreignId('treasury_service_id')->nullable()->after('treasury_entity_id')->constrained('treasury_services')->nullOnDelete();
            $table->foreignId('audit_domain_id')->nullable()->after('treasury_service_id')->constrained('audit_domains')->nullOnDelete();
            $table->foreignId('audit_template_id')->nullable()->after('audit_domain_id')->constrained('audit_templates')->nullOnDelete();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('treasury_service_id')->nullable()->after('mission_id')->constrained('treasury_services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treasury_service_id');
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audit_template_id');
            $table->dropConstrainedForeignId('audit_domain_id');
            $table->dropConstrainedForeignId('treasury_service_id');
            $table->dropConstrainedForeignId('treasury_entity_id');
        });

        Schema::dropIfExists('audit_templates');
        Schema::dropIfExists('audit_domains');
        Schema::dropIfExists('treasury_services');
        Schema::dropIfExists('treasury_entities');
    }
};
