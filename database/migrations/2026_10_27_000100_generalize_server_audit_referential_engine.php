<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('server_audit_control_definitions')) {
            Schema::create('server_audit_control_definitions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('methodology_requirement_id')->nullable();
                $table->foreign('methodology_requirement_id', 'sa_control_requirement_fk')->references('id')->on('methodology_requirements')->nullOnDelete();
                $table->foreignId('control_measure_id')->nullable();
                $table->foreign('control_measure_id', 'sa_control_measure_fk')->references('id')->on('control_measures')->nullOnDelete();
                $table->string('code', 96)->unique();
                $table->unsignedInteger('version')->default(1);
                $table->string('title');
                $table->string('category')->nullable();
                $table->string('criticality', 24);
                $table->text('control_objective')->nullable();
                $table->text('default_expected')->nullable();
                $table->text('risk_description')->nullable();
                $table->text('treatment_guidance');
                $table->json('applicability')->nullable();
                $table->boolean('active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('server_audit_control_references')) {
            Schema::create('server_audit_control_references', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('server_audit_control_definition_id');
                $table->foreign('server_audit_control_definition_id', 'sa_reference_definition_fk')->references('id')->on('server_audit_control_definitions')->cascadeOnDelete();
                $table->string('authority', 100);
                $table->string('document_title');
                $table->string('document_version')->nullable();
                $table->string('chapter')->nullable();
                $table->string('section')->nullable();
                $table->string('provision_reference')->nullable();
                $table->text('requirement_summary')->nullable();
                $table->text('source_url')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('server_audit_assets', function (Blueprint $table): void {
            if (! Schema::hasColumn('server_audit_assets', 'asset_type')) $table->string('asset_type')->default('server')->after('platform');
            if (! Schema::hasColumn('server_audit_assets', 'product')) $table->string('product')->nullable()->after('asset_type');
            if (! Schema::hasColumn('server_audit_assets', 'product_version')) $table->string('product_version')->nullable()->after('product');
        });

        Schema::table('server_audit_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('server_audit_results', 'server_audit_control_definition_id')) {
                $table->foreignId('server_audit_control_definition_id')->nullable()->after('control_code');
                $table->foreign('server_audit_control_definition_id', 'sa_result_definition_fk')->references('id')->on('server_audit_control_definitions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Migration corrective volontairement non destructive : les preuves historiques doivent être conservées.
    }
};
